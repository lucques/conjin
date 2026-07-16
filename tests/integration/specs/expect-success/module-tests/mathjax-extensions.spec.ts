import { expect, test } from "../../../fixtures/page-health";

const fixturePath = "/module-tests-expect-success/mathjax-extensions/";

test.describe("mathjax-extensions", () => {
  test("loads MathJax transitively and renders extension-generated math", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto(fixturePath);

    expect(response?.ok()).toBe(true);
    await expect(page.locator("#MathJax-script")).toHaveCount(1);
    await expect(page.locator("#tightarray-fixture mjx-container")).toHaveCount(1);
    await expect(page.locator("#equation-set-fixture mjx-container")).toHaveCount(1);
    await expect(page.locator("#helper-fixture mjx-container")).toHaveCount(1);
    await expect(page.locator("mjx-merror")).toHaveCount(0);

    const mathJaxIsAvailable = await page.evaluate(() =>
      typeof (window as unknown as { MathJax?: unknown }).MathJax === "object"
    );
    expect(mathJaxIsAvailable).toBe(true);
  });

  test("runs post-render hooks and exposes number-formatting helpers", async ({ healthyPage }) => {
    const { page } = healthyPage;
    await page.goto(fixturePath);

    await expect(page.locator("#mathjax-hook-status")).toHaveText("hook called");

    const formatted = await page.evaluate(() => {
      const extensionWindow = window as unknown as {
        mj_num: (value: number, decimalPlaces?: number) => string;
        mj_num_parens: (value: number, decimalPlaces?: number) => string;
      };

      return {
        integer: extensionWindow.mj_num(12),
        decimal: extensionWindow.mj_num(12.5),
        negative: extensionWindow.mj_num_parens(-2.75),
      };
    });

    expect(formatted).toEqual({
      integer: "12{\\phantom{,}}{\\phantom{0}}{\\phantom{0}}",
      decimal: "12{,}5{\\phantom{0}}",
      negative: "(-2{,}75)",
    });
  });
});
