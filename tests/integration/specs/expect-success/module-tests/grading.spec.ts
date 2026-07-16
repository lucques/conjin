import { expect, test } from "../../../fixtures/page-health";

const fixturePath = "/module-tests-expect-success/grading/";

const readTable = async (
  page: import("@playwright/test").Page,
  kind: "reference" | "computed",
  name: string,
): Promise<string[][]> =>
  page
    .locator(`table[data-grading-kind="${kind}"][data-grading-name="${name}"] tr`)
    .evaluateAll((rows) =>
      rows.map((row) =>
        Array.from(row.querySelectorAll("th, td"), (cell) => cell.textContent!.trim()),
      ),
    );

test.describe("grading", () => {
  test("prints totals and calculated grades in a grade list", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto(fixturePath);

    expect(response?.ok()).toBe(true);

    const rows = page.locator("#grade-list tbody tr");
    await expect(rows).toHaveCount(3);
    await expect(page.locator("#grade-list thead tr").nth(1).locator("th")).toHaveText([
      "",
      "",
      "8",
      "12",
      "− 2",
      "18",
      "",
    ]);
    await expect(rows.nth(0).locator("td")).toHaveText([
      "Lovelace",
      "Ada",
      "7",
      "9",
      "1",
      "17",
      "2",
    ]);
    await expect(rows.nth(1).locator("td")).toHaveText([
      "Hopper",
      "Grace",
      "8",
      "10",
      "0",
      "18",
      "1",
    ]);
    await expect(rows.nth(2).locator("td")).toHaveText([
      "Noether",
      "Emmy",
      "",
      "",
      "",
      "",
      "",
    ]);
  });

  for (const id of [
    "sekundarstufe-1",
    "sekundarstufe-2-tests",
    "sekundarstufe-2-exams",
  ] as const) {
    test(`matches every threshold for ${id}`, async ({ healthyPage }) => {
      const { page } = healthyPage;
      await page.goto(fixturePath);

      const reference = await readTable(page, "reference", id);
      const computed = await readTable(page, "computed", id);

      expect(computed.length).toBe(reference.length);
      expect(computed[0]).toEqual(reference[0]);

      const mismatches: {
        maximumPoints: string;
        grade: string;
        reference: string;
        computed: string;
      }[] = [];

      for (let row = 1; row < reference.length; row++) {
        expect(computed[row].length).toBe(reference[row].length);

        const maximumPoints = reference[row][0];
        for (let column = 1; column < reference[row].length; column++) {
          if (computed[row][column] === reference[row][column]) {
            continue;
          }

          const mismatch = {
            maximumPoints,
            grade: reference[0][column],
            reference: reference[row][column],
            computed: computed[row][column],
          };
          mismatches.push(mismatch);
          console.log(
            `${id}: maximum points ${mismatch.maximumPoints}, grade ${mismatch.grade}: reference ${mismatch.reference}, computed ${mismatch.computed}`,
          );
        }
      }

      expect(mismatches).toEqual([]);
    });
  }
});
