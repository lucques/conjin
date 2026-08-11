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
    const origin = new URL(page.url()).origin;
    await expect(alternatives).toHaveCount(3);
    await expect(alternatives.nth(0)).toHaveAttribute("hreflang", "de");
    await expect(alternatives.nth(0)).toHaveAttribute("href", `${origin}${fixtureRoot}/de/`);
    await expect(alternatives.nth(1)).toHaveAttribute("hreflang", "en");
    await expect(alternatives.nth(1)).toHaveAttribute("href", `${origin}${fixtureRoot}/en/`);
    await expect(alternatives.nth(2)).toHaveAttribute("hreflang", "x-default");
    await expect(alternatives.nth(2)).toHaveAttribute("href", `${origin}/`);

    const languageLinks = page.locator("#sidebar-buttons a[data-bs-title]");
    await expect(languageLinks).toHaveCount(3);
    await expect.poll(() => languageLinks.evaluateAll((links) =>
      links.map((link) => link.getAttribute("data-bs-title")),
    )).toEqual(["English", "Deutsch", "Français"]);
    await expect(languageLinks.nth(0)).toHaveAttribute(
      "href",
      `${fixtureRoot}/en/?localization-explicit-choice=1`,
    );
    await expect(languageLinks.nth(1)).toHaveClass(/(?:^|\s)active(?:\s|$)/);

    await languageLinks.nth(0).click();
    await expect(page).toHaveURL(new RegExp(`${fixtureRoot}/en/$`));
    await expect(page.locator("html")).toHaveAttribute("lang", "en");
    await expect(page.locator("#localized-content")).toHaveText("English content");
    await expect(page.locator("#remembered-language")).toHaveText("en");
    await expect(page.locator('head link[hreflang="x-default"]')).toHaveAttribute(
      "href",
      `${origin}/`,
    );
    await expect(page.locator("#sidebar-buttons a[data-bs-title='English']")).toHaveClass(
      /(?:^|\s)active(?:\s|$)/,
    );
    await expect.poll(async () => {
      const cookies = await page.context().cookies();
      return cookies.find((cookie) => cookie.name === "conjin_language")?.value;
    }).toBe("en");
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
    await expect(frenchLink).toHaveAttribute(
      "href",
      `${fixtureRoot}/fr-unavailable/?localization-explicit-choice=1`,
    );
    await expect(frenchLink).toHaveClass(/(?:^|\s)active(?:\s|$)/);
  });

  test("negotiates the Accept-Language header against configured languages", async ({ request }) => {
    const cases = [
      ["de-DE,de;q=0.9,en;q=0.8", "de"],
      ["fr-CA,de;q=0.8", "fr"],
      ["de-DE;q=0.8,en;q=0.9", "en"],
      ["DE-de;Q=1", "de"],
      ["*;q=1,en;q=0.5", "de"],
      ["*;q=1,de;q=0", "en"],
      ["de;q=0,en;q=0.5", "en"],
      ["not-valid;q=nope,fr;q=0.7", "fr"],
      ["de;q=0,en;q=0,fr;q=0", ""],
      ["", ""],
    ];

    for (const [acceptLanguage, expected] of cases) {
      const response = await request.get(`${fixtureRoot}/en/`, {
        headers: { "Accept-Language": acceptLanguage },
      });
      expect(response.ok()).toBe(true);
      const varyFields = response.headers()["vary"].split(",").map((field) => field.trim().toLowerCase());
      expect(varyFields).toEqual(expect.arrayContaining(["cookie", "accept-language"]));
      expect(await response.text()).toContain(`<p id="accepted-language">${expected}</p>`);
    }
  });
});
