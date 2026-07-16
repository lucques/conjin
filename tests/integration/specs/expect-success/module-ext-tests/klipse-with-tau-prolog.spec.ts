import { expect, test } from "../../../fixtures/page-health";

const fixturePath = "/module-ext-tests-expect-success/klipse-with-tau-prolog/";

test.describe("klipse-with-tau-prolog", () => {
  test("loads Tau Prolog and renders the interpreter without browser errors", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto(fixturePath);

    expect(response?.ok()).toBe(true);
    await expect(
      page.locator('script[src$="/modules-shared-ext/klipse-with-tau-prolog/res/tau-prolog.js"]'),
    ).toHaveCount(1);
    await expect(
      page.locator('script[src$="/modules-shared-ext/klipse-with-tau-prolog/res/tau-prolog-interface.js"]'),
    ).toHaveCount(1);
    await expect(page.locator("#tau-prolog-interpreter .tauprolog.app-interpreter")).toBeVisible();
    await expect(page.getByText("Fakten und Regeln:")).toBeVisible();
    await expect(page.getByRole("button", { name: "Einlesen" })).toBeVisible();
    await expect(page.getByRole("button", { name: "Abfragen" })).toBeVisible();
  });
});
