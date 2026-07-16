import { expect, test } from "../../../fixtures/page-health";

const unrestrictedPath = "/module-tests-expect-success/sol-mode/unrestricted/";
const restrictedPath = "/module-tests-expect-success/sol-mode/restricted/";
const solutionToggleSelector = "#sidebar-buttons button:has(.bi-lightbulb)";

test.describe("sol-mode", () => {
  test("renders unrestricted content in exercise mode by default", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto(unrestrictedPath);

    expect(response?.ok()).toBe(true);
    await expect(page.locator("#question")).toHaveCount(1);
    await expect(page.locator("#solution")).toHaveCount(0);
    await expect(page.locator("#gap-answer")).toHaveCount(0);
    await expect(page.locator("#gap-placeholder")).toHaveCount(1);
    await expect(page.locator(solutionToggleSelector)).toHaveCount(1);
    await expect(page.locator(solutionToggleSelector)).toHaveAttribute("data-bs-title", "Lösung einblenden");
  });

  test("reveals unrestricted solutions when requested", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto(`${unrestrictedPath}?solution=1`);

    expect(response?.ok()).toBe(true);
    await expect(page.locator("#solution")).toHaveCount(1);
    await expect(page.locator("#gap-answer")).toHaveCount(1);
    await expect(page.locator("#gap-placeholder")).toHaveCount(0);
    await expect(page.locator(solutionToggleSelector)).toHaveClass(/(?:^|\s)active(?:\s|$)/);
    await expect(page.locator(solutionToggleSelector)).toHaveAttribute("data-bs-title", "Lösung ausblenden");
  });

  test("ignores unauthorized solution requests and hides the toggle", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto(`${restrictedPath}?solution=1`);

    expect(response?.ok()).toBe(true);
    await expect(page.locator("#question")).toHaveCount(1);
    await expect(page.locator("#solution")).toHaveCount(0);
    await expect(page.locator("#gap-answer")).toHaveCount(0);
    await expect(page.locator("#gap-placeholder")).toHaveCount(1);
    await expect(page.locator(solutionToggleSelector)).toHaveCount(0);
  });

  test("allows an authorized static user to toggle solutions", async ({ healthyPage }) => {
    const { page } = healthyPage;
    await page.goto(`/login/?redirect=${encodeURIComponent(restrictedPath)}`);
    await page.locator("#password").fill("admin");
    await page.getByRole("button", { name: "Login" }).click();

    await expect(page).toHaveURL(restrictedPath);
    await expect(page.locator("#solution")).toHaveCount(0);
    const solutionToggle = page.locator(solutionToggleSelector);
    await expect(solutionToggle).toHaveCount(1);

    await Promise.all([
      page.waitForURL(`${restrictedPath}?solution=1`),
      solutionToggle.click(),
    ]);

    await expect(page.locator("#solution")).toHaveCount(1);
    await expect(page.locator("#gap-answer")).toHaveCount(1);
    await expect(page.locator("#gap-placeholder")).toHaveCount(0);
  });
});
