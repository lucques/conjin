import { expect, test } from "../../../fixtures/page-health";

const fixturePath = "/module-ext-tests-expect-success/jsxgraph/";

test.describe("jsxgraph", () => {
  test("loads its assets and renders a board without browser errors", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto(fixturePath);

    expect(response?.ok()).toBe(true);
    await expect(
      page.locator(
        'link[rel="stylesheet"][href$="/modules-shared-ext/jsxgraph/res/jsxgraph.css"]',
      ),
    ).toHaveCount(1);
    await expect(
      page.locator(
        'script[src$="/modules-shared-ext/jsxgraph/res/jsxgraphcore.js"]',
      ),
    ).toHaveCount(1);
    await expect(page.locator("#jsxgraph-board svg")).toHaveCount(1);

    expect(
      await page.evaluate(
        () =>
          typeof (
            window as typeof window & {
              JXG?: { JSXGraph?: { initBoard?: unknown } };
            }
          ).JXG?.JSXGraph?.initBoard,
      ),
    ).toBe("function");
  });
});
