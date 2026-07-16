import { expect, test } from "../../../fixtures/page-health";

test.describe("fullcalendar", () => {
  test("renders the default calendar without browser errors", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto("/module-demos-ext/fullcalendar/");

    expect(response?.ok()).toBe(true);
    await expect(page.locator("#the-calendar .fc-view-harness")).toBeVisible();
    await expect(page.locator("#the-calendar")).toHaveClass(/(?:^|\s)fc-theme-standard(?:\s|$)/);
  });

  test("renders the Bootstrap calendar without browser errors", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto("/module-demos-ext/fullcalendar/bootstrap/");

    expect(response?.ok()).toBe(true);
    await expect(page.locator("#the-calendar .fc-view-harness")).toBeVisible();
    await expect(page.locator("#the-calendar")).toHaveClass(/(?:^|\s)fc-theme-bootstrap5(?:\s|$)/);
  });
});
