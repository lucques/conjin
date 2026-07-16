import { expect, test } from "../../../fixtures/page-health";

test.describe("jsxgraph-helpers", () => {
  test("loads a board and the helper-generated initialization without errors", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto("/module-tests-expect-success/jsxgraph-helpers/");

    expect(response?.ok()).toBe(true);
    await expect(page.locator(".jsxgraph-plot")).toHaveCount(1);
    await expect(page.locator("#jsxgraph-status")).toHaveAttribute("data-ready", "true");

    const jsxGraphIsAvailable = await page.evaluate(() => {
      const jsxGraph = (window as unknown as {
        JXG?: { JSXGraph?: { initBoard?: unknown } };
      }).JXG;
      return typeof jsxGraph?.JSXGraph?.initBoard === "function";
    });
    expect(jsxGraphIsAvailable).toBe(true);
  });
});
