import { expect, test } from "../../../fixtures/page-health";

const fixturePath = "/module-tests-expect-success/subpages-all/";
const fixtureIds = "module-tests-expect-success subpages-all";

test.describe("subpages-all", () => {
  test("discovers eligible directories in name order", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto(fixturePath);

    expect(response?.ok()).toBe(true);

    const fixture = page.locator(`.nav-tree-item[data-ids="${fixtureIds}"]`);
    const children = fixture.locator(":scope > ul > li > .nav-tree-item");

    await expect(children).toHaveCount(2);
    expect(await children.evaluateAll((items) => items.map((item) => item.getAttribute("data-ids")))).toEqual([
      `${fixtureIds} alpha`,
      `${fixtureIds} zeta`,
    ]);
    await expect(page.locator(`.nav-tree-item[data-ids="${fixtureIds} inc"]`)).toHaveCount(0);
    await expect(page.locator(`.nav-tree-item[data-ids="${fixtureIds} res"]`)).toHaveCount(0);
  });
});
