import { expect, test } from "../../../fixtures/page-health";

const fixturePath = "/module-ext-tests-expect-success/nerdamer/";

test.describe("nerdamer", () => {
  test("loads the library and evaluates an expression without browser errors", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto(fixturePath);

    expect(response?.ok()).toBe(true);
    await expect(
      page.locator('script[src$="/modules-shared-ext/nerdamer/res/all.min.js"]'),
    ).toHaveCount(1);
    await expect(page.locator("#nerdamer-result")).toHaveText(
      "1+3*x+3*x^2+x^3",
    );

    expect(
      await page.evaluate(
        () => typeof (window as typeof window & { nerdamer?: unknown }).nerdamer,
      ),
    ).toBe("function");
  });
});
