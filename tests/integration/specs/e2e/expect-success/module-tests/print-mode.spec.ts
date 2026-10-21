import { firefox } from '@playwright/test';
import { readFile } from 'node:fs/promises';
import { inflateSync } from 'node:zlib';
import { expect, test } from "../../../../fixtures/page-health";

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
  test.describe(`print-mode with ${fixture.template}`, () => {
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
        const rendererConfig = await preview.locator("body").evaluate(() => ({
          prismManual: Prism.manual,
          mathjaxAutoTypeset: MathJax.config.startup.typeset,
        }));
        expect(rendererConfig).toEqual({ prismManual: true, mathjaxAutoTypeset: false });
        await expect(preview.locator(".pagedjs_a5-portrait_page")).toHaveCount(1);
        await expect(preview.locator(".pagedjs_a5-landscape_page")).toHaveCount(1);
      }
      await expect(preview.locator("mjx-container")).toHaveCount(
        fixture.usesMathJax ? 1 : 0,
      );

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

test("page-prep ignores stylesheet links without a URL", async ({ page }) => {
  await page.addInitScript(() => {
    document.addEventListener("DOMContentLoaded", () => {
      for (const href of [null, ""]) {
        const link = document.createElement("link");
        link.rel = "stylesheet";
        if (href !== null) link.setAttribute("href", href);
        // Firefox reports no sheet for these extension links.
        Object.defineProperty(link, "sheet", { value: null });
        document.head.append(link);
      }
    }, { once: true });
  });

  await page.goto(`${fixtures[1].path}?wrapper=0`);
  await page.evaluate(() => window.printModePromise);
  await expect(page.locator(".pagedjs_pages")).toHaveCount(1);
});

test("print-mode waits for the complete document before paginating", async ({ page }) => {
  const fixture = fixtures.find(({ template }) => template === "template-exam")!;
  let releaseResource!: () => void;
  let markResourceRequested!: () => void;
  const resourceReleased = new Promise<void>((resolve) => {
    releaseResource = resolve;
  });
  const resourceRequested = new Promise<void>((resolve) => {
    markResourceRequested = resolve;
  });

  await page.route("**/template-exam/res/load-gate.svg", async (route) => {
    markResourceRequested();
    await resourceReleased;
    await route.continue();
  });

  await Promise.all([
    page.goto(`${fixture.path}?wrapper=0&load_gate=1`, {
      waitUntil: "domcontentloaded",
    }),
    resourceRequested,
  ]);
  await expect(page.locator("#print-mode-load-gate")).toHaveCount(1);
  await expect(page.locator(".pagedjs_pages")).toHaveCount(0);

  releaseResource!();
  await page.waitForLoadState("load");
  await expect(page.locator(".pagedjs_pages")).toHaveCount(1);
});

test("print-mode waits for SQL rendering, typesets inserted math and finalizes layout once", async ({ healthyPage }) => {
  const { page } = healthyPage;
  await page.goto(`${fixtures[0].path}?wrapper=0&async_sql=1&layout_probe=1`);
  await page.evaluate(() => {
    window.layoutRuns = 0;
    window.readyEvents = 0;
    window.addEventListener("print-mode-ready", () => window.readyEvents++);
  });
  await expect(page.locator(".pagedjs_pages")).toHaveCount(0);
  expect(await page.evaluate(() => window.printModeReady)).toBe(false);
  expect(await page.evaluate(() => setupPrintMode("unused.css") === window.printModePromise)).toBe(true);
  await page.evaluate(() => window.releaseSql());
  await page.evaluate(() => window.printModePromise);
  await expect(page.locator(".pagedjs_pages #delayed-sql-tables table")).toHaveCount(2);
  await expect(page.locator(".pagedjs_pages #delayed-sql-tables mjx-container")).toHaveCount(2);
  const state = await page.evaluate(() => ({ ...window.beforePagination, layouts: window.layoutRuns, ready: window.readyEvents }));
  expect(state.tables).toBe(2);
  expect(state.math).toBe(2);
  expect(state.widths[0]).toBe(state.widths[1]);
  expect(parseFloat(state.widths[0])).toBeGreaterThan(0);
  expect(state.layouts).toBe(1);
  expect(state.ready).toBe(1);
});

test("print-mode waits for an eager image inserted after window.load", async ({ healthyPage }) => {
  const { page } = healthyPage;
  let releaseImage!: () => void;
  const imageGate = new Promise<void>(resolve => { releaseImage = resolve; });
  const imageRequested = page.waitForRequest("**/delayed-print-image.svg");
  await page.route("**/delayed-print-image.svg", async route => {
    await imageGate;
    await route.fulfill({ contentType: "image/svg+xml", body: '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="30"><rect width="40" height="30"/></svg>' });
  });
  await page.goto(`${fixtures[0].path}?wrapper=0&async_sql=1&image_on_release=1`);
  await page.evaluate(() => window.releaseSql());
  await imageRequested;
  await expect(page.locator(".pagedjs_pages")).toHaveCount(0);
  releaseImage();
  await page.evaluate(() => window.printModePromise);
  await expect(page.locator(".pagedjs_pages #late-print-image")).toHaveCount(1);
});

test("an explicitly lazy image does not hold resource readiness", async ({ healthyPage }) => {
  const { page } = healthyPage;
  let releaseImage!: () => void;
  const imageGate = new Promise<void>(resolve => { releaseImage = resolve; });
  await page.route("**/delayed-print-image.svg", async route => {
    await imageGate;
    await route.fulfill({ contentType: "image/svg+xml", body: '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="30"/>' });
  });
  await page.goto(`${fixtures[0].path}?wrapper=0&async_sql=1&image_on_release=1&lazy_image=1`);
  await page.evaluate(() => window.releaseSql());
  await page.evaluate(() => pagePrep.wait("layoutResourcesReady"));
  releaseImage();
  await page.evaluate(() => window.printModePromise);
  await expect(page.locator(".pagedjs_pages #late-print-image")).toHaveAttribute("loading", "lazy");
});

test("failed SQL preparation preserves the source document and never enables printing", async ({ page }) => {
  await page.goto(`${fixtures[0].path}?wrapper=0&async_sql=1&sql_error=1`);
  await page.evaluate(() => {
    window.readyEvents = 0;
    window.addEventListener("print-mode-ready", () => window.readyEvents++);
    window.releaseSql();
  });
  expect(await page.evaluate(() => window.printModePromise.then(() => "ready", error => error.message)))
    .toContain("no such table: missing_table");
  await expect(page.locator("#print-mode-error")).toContainText("sql-results:");
  await expect(page.locator("#first-page")).toBeVisible();
  await expect(page.locator(".pagedjs_pages")).toHaveCount(0);
  expect(await page.evaluate(() => ({ ready: window.printModeReady, events: window.readyEvents })))
    .toEqual({ ready: false, events: 0 });
});

test("print-mode waits for concurrent final page-prep tasks", async ({ healthyPage }) => {
  const { page } = healthyPage;
  await page.goto(`${fixtures[0].path}?wrapper=0&async_sql=1&layout_gate=1`);
  await page.evaluate(() => window.releaseSql());
  await expect.poll(() => page.evaluate(() => window.layoutSteps)).toEqual(["first", "second"]);
  await expect(page.locator(".pagedjs_pages")).toHaveCount(0);
  await page.evaluate(() => window.releaseLayout());
  await page.evaluate(() => window.printModePromise);
  await expect(page.locator(".pagedjs_pages")).toHaveCount(1);
});

test("page-prep includes children and rejects unrelated late roots", async ({ page }) => {
  await page.goto(`${fixtures[0].path}?wrapper=0&prep_child=1`, { waitUntil: "domcontentloaded" });
  const stable = await page.evaluate(() => pagePrep.wait("initialContentReady") === pagePrep.wait("initialContentReady"));
  expect(stable).toBe(true);
  await expect(page.locator(".pagedjs_pages")).toHaveCount(0);
  await page.evaluate(() => window.releaseParent());
  await expect.poll(() => page.evaluate(() => typeof window.releaseChild)).toBe("function");
  await expect(page.locator(".pagedjs_pages")).toHaveCount(0);
  expect(await page.evaluate(() => {
    try {
      pagePrep.track({ id: "fixture:late", before: "initialContentReady", promise: Promise.resolve() });
    }
    catch (error) {
      return error instanceof Error ? error.message : String(error);
    }
  })).toContain("registration is closed");
  await page.evaluate(() => window.releaseChild());
  await page.evaluate(() => window.printModePromise);
  await expect(page.locator(".pagedjs_pages")).toHaveCount(1);
});

test("a failed included image rejects preparation and prevents pagination", async ({ page }) => {
  await page.goto(`${fixtures[0].path}?wrapper=0&broken_image=1`);
  const failure = await page.evaluate(() => window.printModePromise.then(() => null, error => ({
    id: error.taskId,
    barrier: error.barrier,
    message: error.message,
  })));
  expect(failure?.id).toBe("page-prep:resources");
  expect(failure?.barrier).toBe("layoutResourcesReady");
  await expect(page.locator(".pagedjs_pages")).toHaveCount(0);
  await expect(page.locator("#print-mode-error")).toBeVisible();
});

for (const barrier of ['initialContentReady', 'renderedContentReady', 'layoutResourcesReady', 'readyForPagination']) {
    test(`task-context wait for ${barrier} after await rejects preparation`, async ({ page }) => {
        await page.addInitScript(() => {
            window.readyEvents = 0;
            window.addEventListener('print-mode-ready', () => window.readyEvents++);
        });
        await page.goto(`${fixtures[0].path}?wrapper=0&prep_invalid_wait=${barrier}`);
        const failure = await page.evaluate(() => window.printModePromise.then(
            () => null,
            error => ({
                taskId: error.taskId,
                barrier: error.barrier,
                cause: error.cause.message,
            }),
        ));
        expect(failure).toEqual({
            taskId: 'fixture:invalid-wait',
            barrier: 'initialContentReady',
            cause: `Page-prep task fixture:invalid-wait cannot wait for ${barrier}: it contributes to that barrier or a preceding one.`,
        });
        await expect(page.locator('#print-mode-error')).toContainText('fixture:invalid-wait');
        await expect(page.locator('.pagedjs_pages')).toHaveCount(0);
        expect(await page.evaluate(() => ({ ready: window.printModeReady, events: window.readyEvents })))
            .toEqual({ ready: false, events: 0 });
    });
}

declare global {
  interface Window {
    beforePagination: { tables: number; math: number; widths: string[] };
    layoutRuns: number;
    layoutSteps: string[];
    printModePromise: Promise<void>;
    printModeReady: boolean;
    readyEvents: number;
    releaseChild: () => void;
    releaseLayout: () => void;
    releaseParent: () => void;
    releaseSql: () => void;
  }

  const MathJax: { config: { startup: { typeset: boolean } } };
  const Prism: { manual: boolean };
  const pagePrep: {
    wait(barrier: string): Promise<void>;
    track(task: { id: string; before: string; promise: Promise<unknown> }): void;
  };
  function setupPrintMode(cssPath: string): Promise<void>;
}


test('exam solutions retain the native preview and wrapper print button', async ({ healthyPage }) => {
    const { page } = healthyPage;
    await page.goto(`${fixtures[1].path}?solution=1`);
    const iframe = await page.locator('#print-mode-iframe').elementHandle();
    const frame = await iframe?.contentFrame();
    expect(frame).toBeTruthy();
    await frame!.waitForFunction(() => window.printModeReady);
    const preview = page.frameLocator('#print-mode-iframe');
    await expect(preview.locator('body')).toHaveClass(/print-mode-native/);
    await expect(preview.locator('.pagedjs_pages')).toHaveCount(0);
    await expect(preview.locator('#first-page')).toBeVisible();
    await expect(preview.locator('#second-page')).toBeVisible();
    await frame!.evaluate(() => {
        window.print = () => { document.body.dataset.printCalled = 'true'; };
    });
    await page.locator('#print-mode-print-button').click();
    await expect(preview.locator('body')).toHaveAttribute('data-print-called', 'true');
});


test('native printing forces breaks between sheets, never after the final sheet', async ({ healthyPage }) => {
    const { page } = healthyPage;
    await page.goto(`${fixtures[1].path}?solution=1&wrapper=0`);
    await page.evaluate(() => window.printModePromise);
    // Renderers may append non-sheet elements after the content.
    await page.locator('main').evaluate(main => main.append(document.createElement('span')));
    await page.emulateMedia({ media: 'print' });
    const breaks = await page.locator('.sheet').evaluateAll(sheets => sheets.map(sheet => ({
        before: getComputedStyle(sheet).breakBefore,
        after: getComputedStyle(sheet).breakAfter,
    })));
    expect(breaks).toEqual([
        { before: 'auto', after: 'auto' },
        { before: 'page', after: 'auto' },
    ]);
});


test('native print PDF has no trailing blank page', async ({ healthyPage }) => {
    const { page } = healthyPage;
    await page.goto(`${fixtures[1].path}?solution=1&wrapper=0`);
    await page.evaluate(() => window.printModePromise);
    const pdf = await page.pdf({ preferCSSPageSize: true });
    expect(pdf.toString('latin1').match(/\/Type \/Page\b/g)).toHaveLength(2);
});


test('Firefox native printing has no trailing unnamed page', async ({ baseURL }, testInfo) => {
    const pdfPath = testInfo.outputPath('native-firefox.pdf');
    const browser = await firefox.launch({
        firefoxUserPrefs: {
            'print.always_print_silent': true,
            'print_printer': 'Mozilla Save to PDF',
            'print.printer_Mozilla_Save_to_PDF.print_to_file': true,
            'print.printer_Mozilla_Save_to_PDF.print_to_filename': pdfPath,
        },
    });
    try {
        const page = await browser.newPage();
        await page.goto(`${baseURL}${fixtures[1].path}?solution=1&wrapper=0`);
        await page.evaluate(() => window.printModePromise);
        await page.evaluate(() => window.print());
        let pdf = '';
        await expect.poll(async () => {
            pdf = await readFile(pdfPath, 'latin1').catch(() => '');
            return pdf.trimEnd().endsWith('%%EOF');
        }, { timeout: 30000 }).toBe(true);
        // Firefox's Cairo PDFs store page dictionaries in compressed object streams.
        let objects = pdf;
        for (const stream of pdf.matchAll(/\/Type \/ObjStm[\s\S]*?stream\r?\n([\s\S]*?)\r?\nendstream/g)) {
            objects += inflateSync(Buffer.from(stream[1], 'latin1')).toString('latin1');
        }
        expect(objects.match(/\/Type\s*\/Page\b/g)).toHaveLength(2);
    }
    finally {
        await browser.close();
    }
});
