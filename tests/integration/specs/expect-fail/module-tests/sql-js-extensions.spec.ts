import { expect, test } from "@playwright/test";

test.describe("sql-js-extensions", () => {
  test("surfaces an invalid query as a JavaScript error", async ({ page }) => {
    const pageError = page.waitForEvent("pageerror");
    const response = await page.goto("/module-tests-expect-fail/sql-js-extensions/query-error/");

    expect(response?.ok()).toBe(true);
    expect((await pageError).message).toContain("no such table: missing_table");
  });
});
