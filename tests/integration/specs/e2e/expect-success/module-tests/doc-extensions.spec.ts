import { expect, test } from "@playwright/test";

const templates = ["template-navigable", "template-generic"] as const;

for (const template of templates) {
  test.describe(`doc-extensions with ${template}`, () => {
    test("places extensions in the expected document regions", async ({ page }) => {
      const response = await page.goto(`/module-tests-expect-success/doc-extensions/${template}/`);

      expect(response?.ok()).toBe(true);
      await expect(page.locator("head meta[data-doc-extension-order]")).toHaveCount(3);
      await expect.soft(page.locator("body")).toHaveClass(/(?:^|\s)doc-extensions-body-class(?:\s|$)/);
      await expect(page.locator("[data-doc-extension-top]")).toHaveCount(2);
      await expect(page.locator("[data-doc-extension-bottom]")).toHaveCount(2);
      await expect(page.locator("#doc-extensions-content")).toHaveCSS("color", "rgb(12, 34, 56)");

      const regionsAreOrdered = await page.evaluate(() => {
        const firstTop = document.querySelector('[data-doc-extension-top="first"]')!;
        const content = document.querySelector("#doc-extensions-content")!;
        const firstBottom = document.querySelector('[data-doc-extension-bottom="first"]')!;

        return Boolean(
          firstTop.compareDocumentPosition(content) & Node.DOCUMENT_POSITION_FOLLOWING
          && content.compareDocumentPosition(firstBottom) & Node.DOCUMENT_POSITION_FOLLOWING
        );
      });
      expect(regionsAreOrdered).toBe(true);
    });

    test("preserves prepend and append ordering", async ({ page }) => {
      await page.goto(`/module-tests-expect-success/doc-extensions/${template}/`);

      await expect.poll(() => page.locator("head meta[data-doc-extension-order]").evaluateAll(
        (elements) => elements.map((element) => element.getAttribute("content")),
      )).toEqual(["prepended", "added-first", "added-last"]);
      await expect.poll(() => page.locator("[data-doc-extension-top]").evaluateAll(
        (elements) => elements.map((element) => element.getAttribute("data-doc-extension-top")),
      )).toEqual(["first", "second"]);
      await expect.poll(() => page.locator("[data-doc-extension-bottom]").evaluateAll(
        (elements) => elements.map((element) => element.getAttribute("data-doc-extension-bottom")),
      )).toEqual(["first", "second"]);
    });

    test("runs DOM-setup JavaScript in insertion order", async ({ page }) => {
      await page.goto(`/module-tests-expect-success/doc-extensions/${template}/`);

      await expect(page.locator("#doc-extensions-content")).toHaveAttribute("data-dom-ready", "yes");
      await expect.poll(() => page.evaluate(() => window.docExtensionsExecutionOrder)).toEqual([
        "first",
        "second",
      ]);
    });
  });
}

declare global {
  interface Window {
    docExtensionsExecutionOrder: string[];
  }
}
