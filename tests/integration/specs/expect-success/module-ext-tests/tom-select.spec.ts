import { expect, test } from "../../../fixtures/page-health";

const fixturePath = "/module-ext-tests-expect-success/tom-select/";

test.describe("tom-select", () => {
  test("loads its assets and enhances a select without browser errors", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto(fixturePath);

    expect(response?.ok()).toBe(true);
    await expect(
      page.locator(
        'link[rel="stylesheet"][href$="/modules-shared-ext/tom-select/res/tom-select.css"]',
      ),
    ).toHaveCount(1);
    await expect(
      page.locator(
        'script[src$="/modules-shared-ext/tom-select/res/tom-select.complete.min.js"]',
      ),
    ).toHaveCount(1);

    const select = page.locator("#tom-select-fruit");
    await expect(select).toHaveClass(/tomselected/);
    await expect(page.locator(".ts-wrapper")).toHaveCount(1);

    await page.locator(".ts-control").click();
    await page.locator(".ts-dropdown .option").filter({ hasText: "Banana" }).click();

    await expect(select).toHaveValues(["banana"]);
    await expect(page.locator('.ts-control [data-value="banana"]')).toHaveText("Banana");
  });
});
