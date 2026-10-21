import { expect, test } from "@playwright/test";

test.describe("db-mysql", () => {
  test("renders the result of a database query", async ({ request }) => {
    const response = await request.get("/module-tests-expect-success/db-mysql/query/");
    const body = await response.text();

    expect(response.status()).toBe(200);
    expect(body).toContain("Live result from DB:");
    expect(body).toContain("Cookie Dough");
  });
});
