import { expect, test } from "../../../fixtures/page-health";

test.describe("timetable", () => {
  test("initializes the calendar without browser errors", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto(
      "/module-tests-expect-success/timetable/?date=2024-01-08"
    );

    expect(response?.ok()).toBe(true);
    await expect(page.locator(".timetable_calendar .fc-view-harness")).toBeVisible();
    await expect(page.locator(".timetable_calendar .fc-event").first()).toContainText(
      "Unterricht"
    );
  });
});
