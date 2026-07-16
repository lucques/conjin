import { expect, test } from "../../../fixtures/page-health";

const fixturePath = "/module-tests-expect-success/sql-js-knowledge-history/";

test.describe("sql-js-knowledge-history", () => {
  test("renders every date granularity and HTML descriptions without JavaScript errors", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto(fixturePath);

    expect(response?.ok()).toBe(true);
    await expect(page.locator('script[src*="/modules-shared/sql-js-knowledge-history/res/library.js"]')).toHaveCount(1);

    const rows = page.locator("#history-result tbody tr");
    await expect(rows).toHaveCount(3);
    await expect(rows.locator("td:first-child")).toHaveText([
      "1776-07-04",
      "1914 to 1918",
      "1915-07 to 1916-03",
    ]);
    await expect(rows.locator("strong")).toHaveText([
      "American Declaration of Independence",
      "First World War",
      "Month range",
    ]);
    await expect(page.locator('[data-event-description="first-world-war"]')).toHaveText(
      "A ranged event with an HTML description.",
    );
  });

  test("persists tags and renders the empty state without JavaScript errors", async ({ healthyPage }) => {
    const { page } = healthyPage;
    await page.goto(fixturePath);

    const tagRows = page.locator("#tag-result tbody tr");
    await expect(tagRows).toHaveCount(3);
    await expect(tagRows).toContainText(["usa", "war", "correspondence"]);
    await expect(page.locator("#empty-history-result")).toHaveText("(keine Einträge)");
    await expect(page.locator("#empty-history-result table")).toHaveCount(0);
  });
});
