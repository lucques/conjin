import { expect, test } from "@playwright/test";

const fixtures = [
  {
    template: "template-navigable",
    path: "/module-tests-expect-success/print-mode/template-navigable/",
    standaloneStylesheet: "print-mode.css",
    usesMathJax: true,
  },
  {
    template: "template-exam",
    path: "/module-tests-expect-success/print-mode/template-exam/",
    standaloneStylesheet: "print-mode.css",
    usesMathJax: false,
  },
  {
    template: "template-interbook",
    path: "/module-tests-expect-success/print-mode/template-interbook/",
    standaloneStylesheet: "print-mode.css",
    usesMathJax: true,
  },
] as const;

for (const fixture of fixtures) {
  test.describe(`print-mode HTTP contracts with ${fixture.template}`, () => {
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
      expect(printHtml).toContain("setupPrintMode(");
      expect(printHtml).toContain("/page-prep/res/page-prep.js");
      expect(printHtml).not.toContain("conjinReady");
      if (fixture.template === "template-navigable") {
        expect(printHtml).toContain('/prism/res/prism.js" data-manual');
        expect(printHtml).toContain("MathJax.startup.typeset = false");
      }
      expect(printHtml).toContain("pagePrep.closeRegistration()");
      expect(printHtml).toContain("/paged-js/res/paged.js");
    });

    if (fixture.template === "template-navigable") {
      test("registers asynchronous SQL results in the standalone document", async ({ request }) => {
        const response = await request.get(`${fixture.path}?wrapper=0&async_sql=1`);

        expect(response.ok()).toBe(true);
        expect(await response.text()).toContain('pagePrep.track({ id: "sql-results:');
      });
    }

  });
}


test('exam solutions use the native renderer in the standalone document', async ({ request }) => {
    const response = await request.get(`${fixtures[1].path}?wrapper=0&solution=1`);
    expect(response.ok()).toBe(true);
    const html = await response.text();
    expect(html).toContain('print-mode-native');
    expect(html).toContain("'native');");
    expect(html).toContain('/print-mode/res/page-a4-portrait.css');
    expect(html).toContain('pagePrep.closeRegistration()');
    expect(html).not.toContain('/paged-js/res/paged.js');
    expect(html).not.toContain('/print-mode/res/interface.css');
});
