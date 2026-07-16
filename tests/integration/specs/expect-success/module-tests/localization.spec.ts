import { expect, test } from "../../../fixtures/page-health";

const fixtureRoot = "/module-tests-expect-success/localization";

test.describe("localization", () => {
  test("publishes translations and switches between localized pages", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto(`${fixtureRoot}/de/`);

    expect(response?.ok()).toBe(true);
    await expect(page.locator("html")).toHaveAttribute("lang", "de");
    await expect(page.locator("#localized-content")).toHaveText("Deutscher Inhalt");

    const alternatives = page.locator('head link[rel="alternate"]');
    await expect(alternatives).toHaveCount(2);
    await expect(alternatives.nth(0)).toHaveAttribute("hreflang", "de");
    await expect(alternatives.nth(0)).toHaveAttribute("href", `${fixtureRoot}/de/`);
    await expect(alternatives.nth(1)).toHaveAttribute("hreflang", "en");
    await expect(alternatives.nth(1)).toHaveAttribute("href", `${fixtureRoot}/en/`);

    const languageLinks = page.locator("#sidebar-buttons a[data-bs-title]");
    await expect(languageLinks).toHaveCount(3);
    await expect.poll(() => languageLinks.evaluateAll((links) =>
      links.map((link) => link.getAttribute("data-bs-title")),
    )).toEqual(["Deutsch", "English", "Français"]);
    await expect(languageLinks.nth(0)).toHaveClass(/(?:^|\s)active(?:\s|$)/);

    await languageLinks.nth(1).click();
    await expect(page).toHaveURL(new RegExp(`${fixtureRoot}/en/$`));
    await expect(page.locator("html")).toHaveAttribute("lang", "en");
    await expect(page.locator("#localized-content")).toHaveText("English content");
    await expect(page.locator("#sidebar-buttons a[data-bs-title='English']")).toHaveClass(
      /(?:^|\s)active(?:\s|$)/,
    );
  });

  test("keeps a nontranslation out of alternate metadata but includes it in the switcher", async ({
    healthyPage,
  }) => {
    const { page } = healthyPage;
    const response = await page.goto(`${fixtureRoot}/fr-unavailable/`);

    expect(response?.ok()).toBe(true);
    await expect(page.locator("html")).toHaveAttribute("lang", "fr");

    const alternatives = page.locator('head link[rel="alternate"]');
    await expect(alternatives).toHaveCount(2);
    await expect.poll(() => alternatives.evaluateAll((links) =>
      links.map((link) => link.getAttribute("hreflang")),
    )).toEqual(["de", "en"]);

    const frenchLink = page.locator("#sidebar-buttons a[data-bs-title='Français']");
    await expect(frenchLink).toHaveAttribute("href", `${fixtureRoot}/fr-unavailable/`);
    await expect(frenchLink).toHaveClass(/(?:^|\s)active(?:\s|$)/);
  });
});
