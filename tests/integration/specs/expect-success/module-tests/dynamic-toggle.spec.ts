import { expect, test } from "../../../fixtures/page-health";

const fixturePath = "/module-tests-expect-success/dynamic-toggle/";

test.describe("dynamic-toggle", () => {
  test("applies and stores configured default states", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto(fixturePath);

    expect(response?.ok()).toBe(true);
    await expect(page.locator("body")).toHaveClass(/(?:^|\s)test-default-on(?:\s|$)/);
    await expect(page.locator("body")).not.toHaveClass(/(?:^|\s)test-default-off(?:\s|$)/);
    await expect.poll(() => page.evaluate(() => ({
      defaultOn: localStorage.getItem("dynamic-toggle-test-default-on"),
      defaultOff: localStorage.getItem("dynamic-toggle-test-default-off"),
    }))).toEqual({
      defaultOn: "true",
      defaultOff: "false",
    });
  });

  test("toggles classes and restores the persisted states after reload", async ({ healthyPage }) => {
    const { page } = healthyPage;
    await page.goto(fixturePath);

    await page.locator("#toggle-default-on").click();
    await page.locator("#toggle-default-off").click();

    await expect(page.locator("body")).not.toHaveClass(/(?:^|\s)test-default-on(?:\s|$)/);
    await expect(page.locator("body")).toHaveClass(/(?:^|\s)test-default-off(?:\s|$)/);
    await expect.poll(() => page.evaluate(() => ({
      defaultOn: localStorage.getItem("dynamic-toggle-test-default-on"),
      defaultOff: localStorage.getItem("dynamic-toggle-test-default-off"),
    }))).toEqual({
      defaultOn: "false",
      defaultOff: "true",
    });

    await page.reload();

    await expect(page.locator("body")).not.toHaveClass(/(?:^|\s)test-default-on(?:\s|$)/);
    await expect(page.locator("body")).toHaveClass(/(?:^|\s)test-default-off(?:\s|$)/);
  });
});
