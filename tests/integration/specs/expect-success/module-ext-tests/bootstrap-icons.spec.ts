import { expect, test } from "../../../fixtures/page-health";

const fixturePath = "/module-ext-tests-expect-success/bootstrap-icons/";

test.describe("bootstrap-icons", () => {
  test("loads its stylesheet and icon font without browser errors", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const fontResponsePromise = page.waitForResponse((response) => {
      const url = new URL(response.url());
      return (
        url.pathname.endsWith(
          "/modules-shared-ext/bootstrap-icons/res/fonts/bootstrap-icons.woff2",
        ) && response.request().resourceType() === "font"
      );
    });
    const response = await page.goto(fixturePath);
    const fontResponse = await fontResponsePromise;

    expect(response?.ok()).toBe(true);
    expect(fontResponse.ok()).toBe(true);
    await expect(
      page.locator(
        'link[rel="stylesheet"][href$="/modules-shared-ext/bootstrap-icons/res/bootstrap-icons.min.css"]',
      ),
    ).toHaveCount(1);

    const iconStyle = await page.locator("#bootstrap-icon").evaluate((element) => {
      const style = getComputedStyle(element, "::before");
      return {
        content: style.content,
        fontFamily: style.fontFamily,
      };
    });
    expect(iconStyle.fontFamily).toContain("bootstrap-icons");
    expect(iconStyle.content).toMatch(/^".+"$/);
  });
});
