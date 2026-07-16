import { expect, test } from "../../../fixtures/page-health";

const fixturePath = "/module-tests-expect-success/bootstrap-alternative/";

test.describe("bootstrap-alternative", () => {
  test("loads every JavaScript controller and its stylesheet without browser errors", async ({
    healthyPage,
  }) => {
    const { page } = healthyPage;
    const response = await page.goto(fixturePath);

    expect(response?.ok()).toBe(true);
    for (const script of ["acc.js", "collapse.js", "modal.js"]) {
      await expect(
        page.locator(
          `script[src*="/modules-shared/bootstrap-alternative/res/${script}"]`,
        ),
      ).toHaveCount(1);
    }
    await expect
      .poll(() =>
        page
          .locator("#bootstrap-alternative-css-probe")
          .evaluate((element) => getComputedStyle(element).display),
      )
      .toBe("none");

    const loadedScripts = await page.evaluate(() =>
      performance
        .getEntriesByType("resource")
        .map((entry) => new URL(entry.name).pathname)
        .filter((path) =>
          path.startsWith("/modules-shared/bootstrap-alternative/res/")
          && path.endsWith(".js")
        )
        .sort()
    );
    expect(loadedScripts).toEqual([
      "/modules-shared/bootstrap-alternative/res/acc.js",
      "/modules-shared/bootstrap-alternative/res/collapse.js",
      "/modules-shared/bootstrap-alternative/res/modal.js",
    ]);
  });

  test("opens accordion items exclusively and synchronizes their controls", async ({
    healthyPage,
  }) => {
    const { page } = healthyPage;
    await page.goto(fixturePath);

    const buttons = page.locator("#accordion-fixture .accordion-button");
    const collapses = page.locator("#accordion-fixture .accordion-collapse");
    await expect(buttons).toHaveCount(2);
    await expect(collapses).toHaveCount(2);
    await expect(collapses.nth(0)).toHaveClass(/(?:^|\s)show(?:\s|$)/);
    await expect(buttons.nth(0)).toHaveAttribute("aria-expanded", "true");
    await expect(buttons.nth(1)).toHaveAttribute("aria-expanded", "false");

    await buttons.nth(1).click();

    await expect(collapses.nth(0)).not.toHaveClass(/(?:^|\s)show(?:\s|$)/);
    await expect(collapses.nth(1)).toHaveClass(/(?:^|\s)show(?:\s|$)/);
    await expect(buttons.nth(0)).toHaveAttribute("aria-expanded", "false");
    await expect(buttons.nth(1)).toHaveAttribute("aria-expanded", "true");
  });

  test("toggles standalone collapses and dispatches lifecycle events", async ({
    healthyPage,
  }) => {
    const { page } = healthyPage;
    await page.goto(fixturePath);

    const trigger = page.locator("#collapse-fixture [data-bs-toggle=\"collapse\"]");
    const collapse = page.locator("#collapse-fixture .collapse");
    await collapse.evaluate((element) => {
      const events: string[] = [];
      for (const name of [
        "show.bs.collapse",
        "shown.bs.collapse",
        "hide.bs.collapse",
        "hidden.bs.collapse",
      ]) {
        element.addEventListener(name, () => events.push(name));
      }
      (element as HTMLElement & { lifecycleEvents: string[] }).lifecycleEvents = events;
    });

    await trigger.click();
    await expect(collapse).toHaveClass(/(?:^|\s)show(?:\s|$)/);
    await expect(trigger).toHaveAttribute("aria-expanded", "true");

    await trigger.click();
    await expect(collapse).not.toHaveClass(/(?:^|\s)show(?:\s|$)/);
    await expect(trigger).toHaveAttribute("aria-expanded", "false");
    expect(
      await collapse.evaluate(
        (element) =>
          (element as HTMLElement & { lifecycleEvents: string[] }).lifecycleEvents,
      ),
    ).toEqual([
      "show.bs.collapse",
      "shown.bs.collapse",
      "hide.bs.collapse",
      "hidden.bs.collapse",
    ]);
  });

  test("opens and dismisses modals while restoring focus and form state", async ({
    healthyPage,
  }) => {
    const { page } = healthyPage;
    await page.goto(fixturePath);

    const trigger = page.locator("#modal-trigger");
    const modal = page.locator("#modal-fixture");
    const input = page.locator("#modal-fixture-input");

    await trigger.click();
    await expect(modal).toHaveClass(/(?:^|\s)show(?:\s|$)/);
    await expect(modal).toHaveAttribute("aria-modal", "true");
    await expect(page.locator("body")).toHaveClass(/(?:^|\s)modal-open(?:\s|$)/);
    await expect(page.locator('.modal-backdrop[data-modal-id="modal-fixture"]')).toHaveCount(1);
    await expect(modal).toBeFocused();

    await input.fill("changed");
    await page.locator("#modal-fixture-close").click();

    await expect(modal).not.toHaveClass(/(?:^|\s)show(?:\s|$)/);
    await expect(input).toHaveValue("initial");
    await expect(page.locator("body")).not.toHaveClass(/(?:^|\s)modal-open(?:\s|$)/);
    await expect(page.locator(".modal-backdrop")).toHaveCount(0);
    await expect(trigger).toBeFocused();

    await trigger.click();
    await page.keyboard.press("Escape");
    await expect(modal).not.toHaveClass(/(?:^|\s)show(?:\s|$)/);
    await expect(trigger).toBeFocused();
  });
});
