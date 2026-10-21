import { expect, test } from "@playwright/test";

test.describe("anchors", () => {
  test("creates and returns semantic anchor paths", async ({ page }) => {
    const response = await page.goto("/module-tests-expect-success/anchors/paths/");

    expect(response?.ok()).toBe(true);
    await expect(page.locator("#first-section")).toHaveAttribute(
      "data-anchor-path",
      '["first-section"]',
    );
    await expect(page.locator("#second-section")).toHaveAttribute(
      "data-anchor-path",
      '["second-section"]',
    );
    await expect(page.locator("#second-section_generated-subsection")).toHaveAttribute(
      "data-anchor-path",
      '["second-section","generated-subsection"]',
    );
  });
});
