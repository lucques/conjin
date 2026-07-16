import { expect, test } from "../../../fixtures/page-health";

const fixturePath = "/module-ext-tests-expect-success/mathjax/";

test.describe("mathjax", () => {
  test("loads the runtime and renders inline and display TeX without browser errors", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto(fixturePath);

    expect(response?.ok()).toBe(true);
    await expect(
      page.locator(
        'script#MathJax-script[src$="/modules-shared-ext/mathjax/res/es5/tex-chtml.js"]',
      ),
    ).toHaveCount(1);
    await expect(page.locator("#mathjax-inline mjx-container")).toHaveCount(1);
    await expect(page.locator("#mathjax-display mjx-container[display=\"true\"]")).toHaveCount(1);
    await expect(page.locator("mjx-merror")).toHaveCount(0);

    expect(
      await page.evaluate(
        () =>
          typeof (
            window as typeof window & {
              MathJax?: unknown;
            }
          ).MathJax,
      ),
    ).toBe("object");
  });
});
