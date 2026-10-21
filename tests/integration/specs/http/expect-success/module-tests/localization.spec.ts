import { expect, test } from "@playwright/test";

const fixtureRoot = "/module-tests-expect-success/localization";

test.describe("localization HTTP contracts", () => {
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
