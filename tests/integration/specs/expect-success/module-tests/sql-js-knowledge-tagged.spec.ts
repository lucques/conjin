import { expect, test } from "../../../fixtures/page-health";

const fixturePath = "/module-tests-expect-success/sql-js-knowledge-tagged/";

test.describe("sql-js-knowledge-tagged", () => {
  test("renders entries and filters them by tag without JavaScript errors", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto(fixturePath);

    expect(response?.ok()).toBe(true);
    await expect(page.locator('script[src*="/modules-shared/sql-js-knowledge-tagged/res/library.js"]')).toHaveCount(1);

    const tags = page.locator('[id^="sql_js_print_tagged_db_tags_"]');
    const items = page.locator('[id^="sql_js_print_tagged_db_items_"]');
    await expect(tags.locator(".tags-all")).toContainText("3");
    await expect(tags.locator(".tags-single")).toHaveCount(3);
    await expect(items.locator(".accordion-item")).toHaveCount(3);
    await expect(items.locator(".accordion-button")).toHaveText(["Alpha", "Beta", "Gamma"]);

    await tags.locator(".tags-single", { hasText: "shared" }).click();

    await expect(page).toHaveURL(/[?&]tag=shared(?:&|$)/);
    await expect(tags.locator(".tags-single", { hasText: "shared" })).toHaveClass(/active/);
    await expect(items.locator(".accordion-item")).toHaveCount(2);
    await expect(items.locator(".accordion-button")).toHaveText(["Alpha", "Beta"]);

    await tags.locator(".tags-all").click();

    await expect(page).not.toHaveURL(/[?&]tag=/);
    await expect(tags.locator(".tags-all")).toHaveClass(/active/);
    await expect(items.locator(".accordion-item")).toHaveCount(3);
  });

  test("expands and collapses every rendered entry", async ({ healthyPage }) => {
    const { page } = healthyPage;
    await page.goto(fixturePath);

    const tags = page.locator('[id^="sql_js_print_tagged_db_tags_"]');
    const items = page.locator('[id^="sql_js_print_tagged_db_items_"]');
    await expect(items.locator(".accordion-collapse")).toHaveCount(3);

    await tags.getByRole("button", { name: "Alle ausklappen" }).click();
    await expect(items.locator(".accordion-collapse.show")).toHaveCount(3);

    await tags.getByRole("button", { name: "Alle einklappen" }).click();
    await expect(items.locator(".accordion-collapse.show")).toHaveCount(0);
  });
});
