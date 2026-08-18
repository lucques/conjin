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

  test("redirects a protected target to its inherited login profile", async ({ request }) => {
    const response = await request.get("/login-profile/child/", { maxRedirects: 0 });

    expect(response.status()).toBe(302);
    expect(response.headers().location).toMatch(
      /\/login\/standalone\/\?redirect=%2Flogin-profile%2Fchild%2F$/,
    );
  });

  test("renders named login profiles and rejects unknown profiles", async ({ request }) => {
    const defaultProfile = await request.get("/login/");
    expect(defaultProfile.status()).toBe(200);
    expect(await defaultProfile.text()).toMatch(/<title>Login<\/title>/);

    const formerlyMagicDefault = await request.get("/login/default/");
    expect(formerlyMagicDefault.status()).toBe(404);

    const standalone = await request.get("/login/standalone/");
    expect(standalone.status()).toBe(200);
    expect(await standalone.text()).toMatch(/<h1>\s*Login\s*<\/h1>/);

    const unknown = await request.get("/login/unknown/");
    expect(unknown.status()).toBe(404);
  });

  test("keeps login pages separate from OIDC protocol endpoints", async ({ request }) => {
    const oldEndpoint = await request.get("/login/openid/iserv", { maxRedirects: 0 });
    expect(oldEndpoint.status()).toBe(404);

    const start = await request.get("/auth/oidc/iserv/start", { maxRedirects: 0 });
    expect(start.status()).toBe(404);

    const callback = await request.get("/auth/oidc/iserv/callback", { maxRedirects: 0 });
    expect(callback.status()).toBe(400);
  });
});
