import { expect, test } from "../../../fixtures/page-health";

const fixturePath = "/module-tests-expect-success/sql-js-extensions/";

test.describe("sql-js-extensions", () => {
  test("loads SQL.js and renders queries from every database initializer", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto(fixturePath);

    expect(response?.ok()).toBe(true);
    await expect(page.locator('script[src*="/modules-shared/sql-js-extensions/res/library.js"]')).toHaveCount(1);

    const inlineTable = page.locator("#inline-query table");
    await expect(inlineTable).toHaveCount(1);
    await expect(inlineTable.locator("thead tr").first()).toContainText("Inventory `${literal}");
    await expect(inlineTable.locator("thead tr").nth(1)).toContainText("display name");
    await expect(inlineTable.locator("tbody tr").nth(0)).toContainText("Chocolate");
    await expect(inlineTable.locator("tbody tr").nth(1)).toContainText("Vanilla `${literal}</script>");
    await expect(inlineTable.locator("tbody tr").last()).toContainText("...");

    const fetchedRows = page.locator("#fetched-query tbody tr");
    await expect(fetchedRows).toHaveCount(2);
    await expect(fetchedRows.nth(0)).toContainText("Strawberry");
    await expect(fetchedRows.nth(1)).toContainText("Mint");

    await expect(page.locator("#path-query tbody")).toContainText("2");

    const databaseFileRows = page.locator("#database-file-query tbody tr");
    await expect(databaseFileRows).toHaveCount(2);
    await expect(databaseFileRows.nth(0)).toContainText("Mint");
    await expect(databaseFileRows.nth(1)).toContainText("Strawberry");
  });

  test("maps queried results to the correct schema and toggles them", async ({ healthyPage }) => {
    const { page } = healthyPage;
    await page.goto(fixturePath);

    const schema = page.locator("#schema-with-results");
    await expect(schema.locator("strong")).toHaveText(["metadata_only", "inventory", "inventory_open"]);

    const inventoryTable = schema.locator("table").nth(1);
    const inventoryBody = inventoryTable.locator("tbody");
    await expect(inventoryBody).toHaveClass(/collapse/);
    await expect(inventoryBody.locator("tr").nth(0)).toContainText("Chocolate");
    await expect(inventoryBody.locator("tr").nth(1)).toContainText("Vanilla `${literal}</script>");
    await expect(inventoryBody.locator("tr").last()).toContainText("...");

    const toggle = schema.locator("button").first();
    await toggle.click();
    await expect(inventoryBody).not.toHaveClass(/collapse/);
    await expect(toggle.locator("i")).toHaveClass(/bi-chevron-down/);

    await inventoryTable.locator("thead").click();
    await expect(inventoryBody).toHaveClass(/collapse/);
    await expect(toggle.locator("i")).toHaveClass(/bi-chevron-right/);

    const openTable = schema.locator("table").nth(2);
    const openToggle = schema.locator("button").nth(1);
    await expect(openTable.locator("tbody")).not.toHaveClass(/collapse/);
    await expect(openToggle.locator("i")).toHaveClass(/bi-chevron-down/);

    await expect(page.locator("#schema-only strong")).toHaveText("audit_log");
  });
});
