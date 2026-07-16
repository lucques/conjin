import { expect, test } from "../../../fixtures/page-health";

test.describe("markdown", () => {
  test("renders representative Markdown as semantic HTML", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto("/module-tests-expect-success/markdown/");

    expect(response?.ok()).toBe(true);

    const output = page.locator("#markdown-output");
    await expect(output.locator("h2")).toHaveText("Markdown contract");
    await expect(output.locator("strong")).toHaveText("strong text");
    await expect(output.locator("ul > li")).toHaveText(["First item", "Second item"]);
  });
});
