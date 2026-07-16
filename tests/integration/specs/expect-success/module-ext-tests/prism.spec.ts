import { expect, test } from "../../../fixtures/page-health";

const fixturePath = "/module-ext-tests-expect-success/prism/";

test.describe("prism", () => {
  test("loads its assets and highlights source code without browser errors", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto(fixturePath);

    expect(response?.ok()).toBe(true);
    await expect(
      page.locator(
        'link[rel="stylesheet"][href$="/modules-shared-ext/prism/res/prism.css"]',
      ),
    ).toHaveCount(1);
    await expect(
      page.locator(
        'script[src$="/modules-shared-ext/prism/res/prism.js"]',
      ),
    ).toHaveCount(1);
    await expect(
      page.locator("#prism-listing .token.keyword").filter({ hasText: "public" }),
    ).toHaveCount(2);
    await expect(
      page.locator("#prism-listing .line-numbers-rows > span"),
    ).toHaveCount(5);
    await expect(
      page.locator("#prism-listing .line-highlight[data-range=\"2\"]"),
    ).toHaveCount(1);
    await expect(
      page.locator(".code-toolbar > .toolbar button").filter({ hasText: "Copy" }),
    ).toHaveCount(1);

    expect(
      await page.evaluate(
        () => typeof (window as typeof window & { Prism?: unknown }).Prism,
      ),
    ).toBe("object");
  });
});
