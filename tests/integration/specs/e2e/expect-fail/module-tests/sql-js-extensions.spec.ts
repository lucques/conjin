import { expect, test } from "@playwright/test";

test.describe("sql-js-extensions", () => {
  test("runs an invalid query without a readiness tracker when page-prep is inactive", async ({ page }) => {
    const queryError = page.waitForEvent("pageerror", error => error.message.includes("no such table: missing_table"));
    const response = await page.goto("/module-tests-expect-fail/sql-js-extensions/query-error/");

    expect(response?.ok()).toBe(true);
    expect(await response?.text()).not.toContain("pagePrep.track");
    expect(await page.evaluate(() => "pagePrep" in window || "conjinReady" in window)).toBe(false);
    expect((await queryError).message).toContain("no such table: missing_table");
  });
});
