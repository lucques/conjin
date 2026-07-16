import { expect, test } from "../../../fixtures/page-health";

const fixturePath = "/module-ext-tests-expect-success/mathjs/";

test.describe("mathjs", () => {
  test("loads the library and evaluates an expression without browser errors", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto(fixturePath);

    expect(response?.ok()).toBe(true);
    await expect(
      page.locator(
        'script[src$="/modules-shared-ext/mathjs/res/math.min.js"]',
      ),
    ).toHaveCount(1);
    await expect(page.locator("#mathjs-result")).toHaveText("5");

    expect(
      await page.evaluate(
        () =>
          typeof (
            window as typeof window & {
              math?: { evaluate?: unknown };
            }
          ).math?.evaluate,
      ),
    ).toBe("function");
  });
});
