import { expect, test } from "../../../fixtures/page-health";

const fixturePath = "/module-ext-tests-expect-success/paged-js/";

test.describe("paged-js", () => {
  test("loads the library and renders a paged preview without browser errors", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto(fixturePath);

    expect(response?.ok()).toBe(true);
    await expect(
      page.locator(
        'script[src$="/modules-shared-ext/paged-js/res/paged.js"]',
      ),
    ).toHaveCount(1);
    await expect(page.locator("#paged-js-status")).toHaveAttribute(
      "data-ready",
      "true",
    );
    await expect(page.locator("#paged-js-status")).toHaveAttribute(
      "data-page-count",
      "1",
    );
    await expect(
      page.locator("#paged-js-preview > .pagedjs_pages"),
    ).toHaveCount(1);
    await expect(
      page.locator("#paged-js-preview .pagedjs_page"),
    ).toHaveCount(1);

    expect(
      await page.evaluate(
        () =>
          typeof (
            window as typeof window & {
              Paged?: { Previewer?: unknown };
            }
          ).Paged?.Previewer,
      ),
    ).toBe("function");
  });
});
