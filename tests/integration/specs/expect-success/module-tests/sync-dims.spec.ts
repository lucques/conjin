import { expect, test } from "../../../fixtures/page-health";

const fixturePath = "/module-tests-expect-success/sync-dims/";

test.describe("sync-dims", () => {
  test("synchronizes matching heights and widths", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto(fixturePath);

    expect(response?.ok()).toBe(true);

    const heights = page.locator('[data-sync-height-id="motivation-4"]');
    const widths = page.locator('[data-sync-width-id="the-p"]');

    await expect(heights).toHaveCount(2);
    await expect(widths).toHaveCount(2);
    await expect.poll(() =>
      heights.evaluateAll((elements) =>
        elements.every((element) => element.clientHeight === elements[0].clientHeight)
      )
    ).toBe(true);
    await expect.poll(() =>
      widths.evaluateAll((elements) =>
        elements.every((element) => element.clientWidth === elements[0].clientWidth)
      )
    ).toBe(true);
  });
});
