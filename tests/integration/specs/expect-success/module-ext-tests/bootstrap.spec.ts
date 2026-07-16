import { expect, test } from "../../../fixtures/page-health";

const fixturePath = "/module-ext-tests-expect-success/bootstrap/";

test.describe("bootstrap", () => {
  test("loads its CSS and JavaScript bundle without browser errors", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto(fixturePath);

    expect(response?.ok()).toBe(true);
    await expect(page.locator('link[rel="stylesheet"][href$="/modules-shared-ext/bootstrap/res/bootstrap.min.css"]')).toHaveCount(1);
    await expect(page.locator('script[src$="/modules-shared-ext/bootstrap/res/bootstrap.bundle.min.js"]')).toHaveCount(1);

    await expect
      .poll(() =>
        page
          .locator("#bootstrap-css-probe")
          .evaluate((element) => getComputedStyle(element).display),
      )
      .toBe("none");
    expect(
      await page.evaluate(
        () =>
          typeof (
            window as typeof window & {
              bootstrap?: { Collapse?: unknown };
            }
          ).bootstrap?.Collapse,
      ),
    ).toBe("function");

    await page.locator("#bootstrap-collapse-toggle").click();
    await expect(page.locator("#bootstrap-collapse")).toHaveClass(/(?:^|\s)show(?:\s|$)/);
    await expect(page.locator("#bootstrap-collapse")).toBeVisible();
  });
});
