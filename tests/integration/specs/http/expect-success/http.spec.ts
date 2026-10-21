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

  for (const path of [
    "/content/index.php",
    "/config.json",
    "/users.json",
    "/conjin/framework/inc/core_init.php",
    "/system/login.php",
    "/vendor/composer/installed.json",
    "/modules-shared/title/default_config.php",
    "/modules-shared/template-generic/templates/target.php",
    "/index.php?req=res&res=../index.php",
    "/module-resource.php?group=modules-shared&module=title&res=../default_config.php",
  ]) {
    test(`does not expose implementation files: ${path}`, async ({ request }) => {
      const response = await request.get(path, { maxRedirects: 0 });
      expect(response.status()).toBe(404);
    });
  }

  for (const filename of ["screen.css", "print.css"]) {
    test(`serves compiled stylesheets at their existing URLs: ${filename}`, async ({ request }) => {
      const response = await request.get(`/modules-shared-css/template-interbook/${filename}`);
      expect(response.status()).toBe(200);
      expect(response.headers()["content-type"]).toContain("text/css");
    });
  }

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
