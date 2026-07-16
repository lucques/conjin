import { expect, test } from "../../../fixtures/page-health";

const structuralFixturePath = "/module-tests-expect-success/nav-view/structural/";
const emptyFixturePath = "/module-tests-expect-success/nav-view/empty/";

test.describe("nav-view authorization filtering", () => {
  test("bases branch structure on visible children", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto(structuralFixturePath);

    expect(response?.ok()).toBe(true);

    const hiddenOnlyParent = page.locator(
      '.nav-tree-item[data-ids="module-tests-expect-success nav-view structural hidden-only-parent"]',
    );
    const visibleParent = page.locator(
      '.nav-tree-item[data-ids="module-tests-expect-success nav-view structural visible-parent"]',
    );
    const hiddenOnlyParentListItem = hiddenOnlyParent.locator("xpath=..");
    const visibleParentListItem = visibleParent.locator("xpath=..");

    await expect(hiddenOnlyParent).toHaveCount(1);
    await expect(hiddenOnlyParent.locator(":scope > ul")).toHaveCount(0);
    await expect(hiddenOnlyParentListItem).not.toHaveClass(/(?:^|\s)nested(?:\s|$)/);
    await expect(hiddenOnlyParentListItem.locator(":scope > .caret")).toHaveCount(0);
    await expect(hiddenOnlyParentListItem.locator(":scope > .bullet")).toHaveCount(1);

    await expect(visibleParent).toHaveCount(1);
    await expect(visibleParent.locator(":scope > ul")).toHaveCount(1);
    await expect(visibleParentListItem).toHaveClass(/(?:^|\s)nested(?:\s|$)/);
    await expect(visibleParentListItem.locator(":scope > .caret")).toHaveCount(1);

    await expect(page.getByText("hidden-child", { exact: true })).toHaveCount(0);
    await expect(page.getByText("visible-child", { exact: true })).toHaveCount(1);
  });

  test("reports a tree with only unauthorized children as empty", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto(emptyFixturePath);

    expect(response?.ok()).toBe(true);
    await expect(page.locator("#nav-tree-empty")).toHaveText("true");
    await expect(page.getByText("hidden-child", { exact: true })).toHaveCount(0);
    await expect(page.locator("#authorization-filtered-nav .nested")).toHaveCount(0);
    await expect(page.locator("#authorization-filtered-nav ul")).toHaveCount(0);
  });
});
