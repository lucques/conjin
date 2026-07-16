import { expect, test } from "../../../fixtures/page-health";

const fixturePath = "/module-ext-tests-expect-success/geogebra/";

test.describe("geogebra", () => {
  test("loads the self-hosted applet and exposes a healthy Apps API", async ({ healthyPage }) => {
    test.setTimeout(60_000);

    const { page } = healthyPage;
    const runtimeResponsePromise = page.waitForResponse((response) => {
      const url = new URL(response.url());
      return url.pathname.endsWith(
        "/modules-shared-ext/geogebra/res/HTML5/5.0/web3d/web3d.nocache.js",
      );
    });
    const response = await page.goto(fixturePath);
    const runtimeResponse = await runtimeResponsePromise;

    expect(response?.ok()).toBe(true);
    expect(runtimeResponse.ok()).toBe(true);
    await expect(
      page.locator('script[src$="/modules-shared-ext/geogebra/res/deployggb.js"]'),
    ).toHaveCount(1);
    await expect(page.locator("#plain-geogebra-applet")).not.toBeEmpty({
      timeout: 45_000,
    });
    await expect(page.locator("#geogebra-status")).toHaveAttribute("data-ready", "true", {
      timeout: 45_000,
    });
    await expect(page.locator("#geogebra-status")).toHaveAttribute(
      "data-command-succeeded",
      "true",
    );

    const apiState = await page.evaluate(() => {
      const api = (
        window as typeof window & {
          plainGeoGebraApi?: {
            evalCommand?: unknown;
            getObjectType: (name: string) => string;
          };
        }
      ).plainGeoGebraApi;

      return {
        evalCommandAvailable: typeof api?.evalCommand === "function",
        objectType: api?.getObjectType("H"),
      };
    });

    expect(apiState).toEqual({
      evalCommandAvailable: true,
      objectType: "point",
    });
  });
});
