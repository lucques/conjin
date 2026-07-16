import { expect, test } from "../../fixtures/page-health";

test.describe("browser behaviour", () => {
  test("runs an in-browser SQL query without browser errors", async ({ healthyPage }) => {
    await healthyPage.page.goto("/module-demos-ext/sql-js/");
    await expect(healthyPage.page.locator("#results")).toContainText("Success! Here is a row:");
  });
});
