import { expect, test } from "@playwright/test";

test.describe("HTTP contracts", () => {
  test("serves the home page", async ({ request }) => {
    const response = await request.get("/");
    expect(response.status()).toBe(200);
    expect(await response.text()).toContain("The integration fixture starts here.");
  });

  test("returns the application 404 page", async ({ request }) => {
    const response = await request.get("/this-target-does-not-exist/");
    expect(response.status()).toBe(404);
  });

  test("redirects anonymous preprocessing requests", async ({ request }) => {
    const response = await request.get("/preprocess/", { maxRedirects: 0 });
    expect(response.status()).toBe(302);
  });
});
