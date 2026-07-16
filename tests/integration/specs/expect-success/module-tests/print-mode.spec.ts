import { expect, test } from "../../../fixtures/page-health";

const fixtures = [
  {
    template: "template-navigable",
    path: "/module-tests-expect-success/print-mode/template-navigable/",
    standaloneStylesheet: "print-mode.css",
  },
  {
    template: "template-exam",
    path: "/module-tests-expect-success/print-mode/template-exam/",
    standaloneStylesheet: "print-mode.css",
  },
  {
    template: "template-interbook",
    path: "/module-tests-expect-success/print-mode/template-interbook/",
    standaloneStylesheet: "print-mode.css",
  },
] as const;

for (const fixture of fixtures) {
  test.describe(`print-mode with ${fixture.template}`, () => {
    test("wrapper=1 is the default wrapper document", async ({ request }) => {
      const defaultResponse = await request.get(fixture.path);
      const explicitWrapperResponse = await request.get(`${fixture.path}?wrapper=1`);

      expect(defaultResponse.ok()).toBe(true);
      expect(explicitWrapperResponse.ok()).toBe(true);

      const defaultHtml = await defaultResponse.text();
      const explicitWrapperHtml = await explicitWrapperResponse.text();
      for (const wrapperHtml of [defaultHtml, explicitWrapperHtml]) {
        expect(wrapperHtml).toContain('id="sidebar"');
        expect(wrapperHtml).toContain('id="print-mode-iframe"');
        expect(wrapperHtml).toContain('src="./?wrapper=0"');
        expect(wrapperHtml).not.toContain('id="first-page"');
        expect(wrapperHtml).not.toContain('class="sheet ');
        expect(wrapperHtml).not.toContain("#print-mode-only");
        expect(wrapperHtml).toContain(`/${fixture.template}/screen.css`);
        expect(wrapperHtml).not.toContain(`/${fixture.template}/print.css`);
        expect(wrapperHtml).toContain('id="print-mode-print-button"');
        expect(wrapperHtml).toContain("bi-printer");
        expect(wrapperHtml).toContain("contentWindow.print()");
      }
    });

    test("wrapper=0 renders only the standalone paged document", async ({ request }) => {
      const response = await request.get(`${fixture.path}?wrapper=0`);

      expect(response.ok()).toBe(true);

      const printHtml = await response.text();
      expect(printHtml).not.toContain('id="sidebar"');
      expect(printHtml).not.toContain('id="print-mode-iframe"');
      expect(printHtml).not.toContain('id="print-mode-print-button"');
      expect(printHtml).toContain('class="sheet a4-portrait"');
      expect(printHtml).toContain('class="sheet a4-landscape"');
      expect(printHtml).toContain("#print-mode-only");
      expect(printHtml).toContain(`/${fixture.template}/${fixture.standaloneStylesheet}`);
      expect(printHtml).not.toContain(`/${fixture.template}/screen.css`);
      expect(printHtml).toContain("/print-mode/res/interface.css");
      expect(printHtml).toContain("/print-mode/res/page-a4-portrait.css");
      expect(printHtml).toContain("setupPrintModeAfterMathJax(");
      expect(printHtml).toContain("/paged-js/res/paged.js");
    });

    test("wrapper embeds the isolated standalone document", async ({ healthyPage }) => {
      const { page } = healthyPage;
      const response = await page.goto(fixture.path);

      expect(response?.ok()).toBe(true);
      await expect(page.locator("#sidebar")).toHaveCount(1);
      await expect(page.locator("#print-mode-iframe")).toHaveAttribute("src", "./?wrapper=0");
      await expect(page.locator("#first-page")).toHaveCount(0);
      await expect(page.locator("#print-mode-print-button")).toHaveCount(1);

      const iframeBox = await page.locator("#print-mode-iframe").boundingBox();
      expect(iframeBox?.width).toBeGreaterThan(500);
      expect(iframeBox?.height).toBeGreaterThan(500);

      const preview = page.frameLocator("#print-mode-iframe");
      await expect(preview.locator(".pagedjs_pages")).toHaveCount(1);
      await expect(preview.locator(".pagedjs_a4-portrait_page")).toHaveCount(1);
      await expect(preview.locator(".pagedjs_a4-landscape_page")).toHaveCount(1);
      if (fixture.template === "template-navigable") {
        await expect(preview.locator(".pagedjs_a5-portrait_page")).toHaveCount(1);
        await expect(preview.locator(".pagedjs_a5-landscape_page")).toHaveCount(1);
      }
      await expect(preview.locator("mjx-container")).toHaveCount(1);

      const iframe = await page.locator("#print-mode-iframe").elementHandle();
      const iframeFrame = await iframe?.contentFrame();
      await iframeFrame?.evaluate(() => {
        window.print = () => {
          document.body.dataset.printCalled = "true";
        };
      });
      await page.locator("#print-mode-print-button").click();
      await expect(preview.locator("body")).toHaveAttribute("data-print-called", "true");

      await iframeFrame?.evaluate(() => {
        document.body.dataset.printCalled = "false";
      });
      await page.keyboard.press("Control+P");
      await expect(preview.locator("body")).toHaveAttribute("data-print-called", "true");
    });
  });
}
