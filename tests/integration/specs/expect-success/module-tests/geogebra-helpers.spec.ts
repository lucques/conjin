import { expect, test } from "../../../fixtures/page-health";

test.describe("geogebra-helpers", () => {
  test("loads the applet and runs the generated Apps API actions without errors", async ({ healthyPage }) => {
    test.setTimeout(60_000);

    const { page } = healthyPage;
    const response = await page.goto("/module-tests-expect-success/geogebra-helpers/");

    expect(response?.ok()).toBe(true);
    await expect(page.locator(".geogebra-applet")).toHaveCount(1);
    await expect(page.locator("#geogebra-status")).toHaveAttribute("data-ready", "true", {
      timeout: 45_000,
    });

    const apiIsAvailable = await page.locator("#geogebra-api").evaluate((element) => {
      const apiVariable = (element as HTMLElement).dataset.apiVariable!;
      const api = (window as unknown as Record<string, { evalCommand?: unknown }>)[apiVariable];
      return typeof api?.evalCommand === "function";
    });
    expect(apiIsAvailable).toBe(true);
  });
});
