import { expect, test } from "../../../fixtures/page-health";

const fixturePath = "/module-tests-expect-success/exercise/";

test.describe("exercise", () => {
  test("renders exercise sections, variants, and pass-through attributes", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto(fixturePath);

    expect(response?.ok()).toBe(true);
    const exercise = page.locator(".accordion.exercise.exercise-contract");
    await expect(exercise).toHaveCount(1);
    await expect(exercise).toHaveAttribute("style", "--exercise-marker: 1;");
    await expect(exercise.locator(".accordion-button").nth(0)).toContainText("Aufgabe 1");
    await expect(exercise.locator(".accordion-button").nth(0)).toContainText("First exercise");
    await expect(exercise.locator(".accordion-item-info")).toContainText("Information");
    await expect(exercise.locator(".accordion-item-orange")).toContainText("Custom hint");
    await expect(exercise.locator(".accordion-item-success")).toContainText("Public solution");
    const exerciseId = await exercise.getAttribute("id");
    expect(exerciseId).not.toBeNull();
    await expect(exercise.locator(".accordion-collapse").first()).toHaveAttribute(
      "data-bs-parent",
      `#${exerciseId}`,
    );
  });

  test("controls numbering and supports unnumbered exercises", async ({ healthyPage }) => {
    const { page } = healthyPage;
    await page.goto(fixturePath);

    const headings = page.locator(".accordion.exercise > .accordion-item:first-child .accordion-button");
    await expect(headings.nth(0)).toContainText("Aufgabe 1");
    await expect(headings.nth(1)).toContainText("Aufgabe 7");
    await expect(headings.nth(1)).toContainText("Renumbered exercise");
    await expect(headings.nth(2)).toHaveText("Unnumbered exercise");
  });

  test("omits hidden and unauthorized solutions", async ({ healthyPage }) => {
    const { page } = healthyPage;
    await page.goto(fixturePath);

    await expect(page.locator("#exercise-public-solution")).toHaveCount(1);
    await expect(page.locator("#exercise-group-restricted-solution")).toHaveCount(0);
    await expect(page.locator("#exercise-user-restricted-solution")).toHaveCount(0);
    await expect(page.locator("#exercise-hidden-solution")).toHaveCount(0);
  });

  test("honours initially open and exclusive accordion behaviour", async ({ healthyPage }) => {
    const { page } = healthyPage;
    await page.goto(fixturePath);

    const exercise = page.locator(".accordion.exercise.exercise-contract");
    const mainCollapse = exercise.locator(".accordion-collapse").nth(0);
    const informationButton = exercise.locator(".accordion-button").nth(1);
    const informationCollapse = exercise.locator(".accordion-collapse").nth(1);

    await expect(mainCollapse).toHaveClass(/(?:^|\s)show(?:\s|$)/);
    await informationButton.click();
    await expect(informationCollapse).toHaveClass(/(?:^|\s)show(?:\s|$)/);
    await expect(mainCollapse).not.toHaveClass(/(?:^|\s)show(?:\s|$)/);
  });
});
