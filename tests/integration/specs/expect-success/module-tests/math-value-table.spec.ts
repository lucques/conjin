import { expect, test } from "../../../fixtures/page-health";

const fixturePath = "/module-tests-expect-success/math-value-table/";

test.describe("math-value-table", () => {
  test("renders expressions with the transitively loaded MathJax runtime", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto(fixturePath);

    expect(response?.ok()).toBe(true);
    await expect(page.locator("#MathJax-script")).toHaveCount(1);
    await expect(page.locator("#value-table-fixture")).toHaveAttribute("data-ready", "true");
    await expect(page.locator("#value-table-fixture table")).toHaveCount(1);
    await expect(page.locator("#value-table-fixture mjx-container")).not.toHaveCount(0);
    await expect(page.locator("mjx-merror")).toHaveCount(0);
  });

  test("updates results and reveals a hidden result", async ({ healthyPage }) => {
    const { page } = healthyPage;
    await page.goto(fixturePath);

    await expect(page.locator("#value-table-fixture")).toHaveAttribute("data-ready", "true");
    const pointRow = page.locator("#value-table-fixture tbody tr").first();
    const input = pointRow.locator('input[type="number"]');
    const visibleResult = pointRow.locator("td").nth(2);
    const hiddenResult = pointRow.locator("td").nth(3);

    await expect(input).toHaveValue("3");
    await expect(visibleResult).toContainText("6");
    await expect(hiddenResult).toHaveClass(/(?:^|\s)opacity-0(?:\s|$)/);

    await input.fill("4");
    await expect(visibleResult).toContainText("8");

    await hiddenResult.click();
    await expect(hiddenResult).not.toHaveClass(/(?:^|\s)opacity-0(?:\s|$)/);
    await expect(hiddenResult).toContainText("5");
  });

  test("adds points and expressions through the extension controls", async ({ healthyPage }) => {
    const { page } = healthyPage;
    await page.goto(fixturePath);

    await expect(page.locator("#value-table-fixture")).toHaveAttribute("data-ready", "true");
    const extensionButtons = page.locator("#value-table-fixture > button");
    const expressionHeadings = page.locator("#value-table-fixture thead td.exp");
    const pointRows = page.locator("#value-table-fixture tbody tr");

    await expect(extensionButtons).toHaveCount(2);
    await expect(expressionHeadings).toHaveCount(2);
    await expect(pointRows).toHaveCount(1);

    await extensionButtons.nth(0).click();
    await expect(expressionHeadings).toHaveCount(3);

    await extensionButtons.nth(1).click();
    await expect(pointRows).toHaveCount(2);
  });
});
