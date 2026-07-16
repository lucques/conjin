import { expect, test } from "../../../fixtures/page-health";

test.describe("source", () => {
  test.beforeEach(async ({ healthyPage }) => {
    const response = await healthyPage.page.goto("/module-tests-expect-success/source/");

    expect(response?.ok()).toBe(true);
  });

  test("integrates its configuration and Prism dependencies with the rendered page", async ({ healthyPage }) => {
    const { page } = healthyPage;

    await expect(page.locator("body")).toHaveClass(/(?:^|\s)language-java(?:\s|$)/);
    await expect(page.locator("body")).toHaveClass(/(?:^|\s)line-numbers(?:\s|$)/);
    await expect(page.locator('head link[href$="/prism.css"]')).toHaveCount(1);
    await expect(page.locator('head script[src$="/prism.js"]')).toHaveCount(1);
    await expect(page.locator("#buffered-listing .token.keyword").filter({ hasText: /^public$/ })).toHaveCount(1);
    await expect(page.locator("#buffered-listing .line-numbers-rows > span")).toHaveCount(4);
  });

  test("escapes literal and buffered source while preserving listing options", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const literal = page.locator("#literal-listing");

    await expect(literal).toHaveText('<section data-value="a&b">Literal HTML</section>');
    await expect(literal.locator("section")).toHaveCount(0);
    await expect(literal).toHaveClass(/(?:^|\s)language-html(?:\s|$)/);
    await expect(literal).toHaveClass(/(?:^|\s)no-line-numbers(?:\s|$)/);
    await expect(literal).toHaveClass(/(?:^|\s)source-fixture(?:\s|$)/);
    await expect(literal).toHaveAttribute("data-start", "7");
    await expect(literal).toHaveAttribute("data-line", "7");
    await expect(literal).toHaveAttribute("style", "--fixture-marker: 1;");
    await expect(literal.locator(".line-numbers-rows")).toHaveCount(0);

    await expect(page.locator("#buffered-listing")).toContainText("private int value = 1;");
    await expect(page.locator("#buffered-listing")).toContainText("<script>unsafe()</script>");
    await expect(page.locator("#buffered-listing > script")).toHaveCount(0);
  });

  test("renders inline code and scoped defaults without interpreting source as markup", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const inline = page.locator("#inline-code");

    await expect(page.locator("#source-inline")).toHaveText("Before <strong>literal</strong> & text after");
    await expect(inline).toHaveText("<strong>literal</strong> & text");
    await expect(inline.locator("strong")).toHaveCount(0);
    await expect(inline).toHaveClass(/(?:^|\s)language-html(?:\s|$)/);
    await expect(inline).toHaveClass(/(?:^|\s)inline-fixture(?:\s|$)/);

    const scope = page.locator("#source-scope > div");
    await expect(scope).toHaveClass(/(?:^|\s)language-css(?:\s|$)/);
    await expect(scope).toHaveClass(/(?:^|\s)no-line-numbers(?:\s|$)/);
    await expect(scope.locator("#scoped-code .token.selector")).toHaveText("a");
  });

  test("loads complete files and extracts blocks with file-based line numbers", async ({ healthyPage }) => {
    const { page } = healthyPage;

    await expect(page.locator("#file-listing")).toContainText('System.out.println("<integration>");');
    await expect(page.locator("#file-listing .line-numbers-rows > span")).toHaveCount(5);

    const block = page.locator("#file-block");
    await expect(block).toHaveAttribute("data-start", "8");
    await expect(block).toHaveText("void second() {\n    int second = 2;\n}\n");
    await expect(block).not.toContainText("first");
    await expect(block.locator(".line-numbers-rows > span")).toHaveCount(3);
  });
});
