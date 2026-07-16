import { expect, test } from "../../../fixtures/page-health";

const fixturePath = "/module-tests-expect-success/favicons/";

test.describe("favicons", () => {
  test("adds both configured favicon links to the document head", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto(fixturePath);

    expect(response?.ok()).toBe(true);

    const shortcutIcon = page.locator('head link[rel="shortcut icon"]');
    await expect(shortcutIcon).toHaveAttribute("type", "image/png");
    await expect(shortcutIcon).toHaveAttribute("sizes", "32x32");
    await expect(shortcutIcon).toHaveAttribute(
      "href",
      "/modules-shared/favicons/res/demo/favicon-32x32.png",
    );

    const appleTouchIcon = page.locator('head link[rel="apple-touch-icon"]');
    await expect(appleTouchIcon).toHaveAttribute("type", "image/png");
    await expect(appleTouchIcon).toHaveAttribute("sizes", "180x180");
    await expect(appleTouchIcon).toHaveAttribute(
      "href",
      "/modules-shared/favicons/res/demo/favicon-apple-touch-icon-180x180.png",
    );
  });

  test("serves valid favicon images at the configured sizes", async ({ healthyPage }) => {
    const { page } = healthyPage;
    await page.goto(fixturePath);

    const dimensions = await page.evaluate(async () => {
      const loadImage = (url: string) => new Promise<[number, number]>((resolve, reject) => {
        const image = new Image();
        image.addEventListener("load", () => resolve([image.naturalWidth, image.naturalHeight]));
        image.addEventListener("error", reject);
        image.src = url;
      });

      return Promise.all([
        loadImage(document.querySelector<HTMLLinkElement>('head link[rel="shortcut icon"]')!.href),
        loadImage(document.querySelector<HTMLLinkElement>('head link[rel="apple-touch-icon"]')!.href),
      ]);
    });

    expect(dimensions).toEqual([[32, 32], [180, 180]]);
  });
});
