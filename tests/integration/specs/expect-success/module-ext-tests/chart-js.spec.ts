import { expect, test } from "../../../fixtures/page-health";

const fixturePath = "/module-ext-tests-expect-success/chart-js/";

test.describe("chart-js", () => {
  test("loads the library and renders a chart without browser errors", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto(fixturePath);

    expect(response?.ok()).toBe(true);
    await expect(
      page.locator('script[src$="/modules-shared-ext/chart-js/res/chart.js"]'),
    ).toHaveCount(1);

    const chartState = await page.locator("#chart-js-canvas").evaluate((canvas) => {
      const chartWindow = window as typeof window & {
        Chart?: {
          getChart: (item: HTMLCanvasElement) => {
            attached: boolean;
            data: { datasets: unknown[] };
          } | undefined;
        };
      };
      const chart = chartWindow.Chart?.getChart(canvas as HTMLCanvasElement);

      return {
        chartAvailable: typeof chartWindow.Chart === "function",
        attached: chart?.attached,
        datasetCount: chart?.data.datasets.length,
        width: (canvas as HTMLCanvasElement).width,
        height: (canvas as HTMLCanvasElement).height,
      };
    });

    expect(chartState).toEqual({
      chartAvailable: true,
      attached: true,
      datasetCount: 1,
      width: expect.any(Number),
      height: expect.any(Number),
    });
    expect(chartState.width).toBeGreaterThan(0);
    expect(chartState.height).toBeGreaterThan(0);
  });
});
