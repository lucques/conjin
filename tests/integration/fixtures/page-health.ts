import { expect, test as base, type Page } from "@playwright/test";

type PageHealth = {
  page: Page;
};

const checkedResourceTypes = new Set(["document", "fetch", "font", "image", "script", "stylesheet", "xhr"]);

export const test = base.extend<{ healthyPage: PageHealth }>({
  healthyPage: async ({ baseURL, page }, use) => {
    const problems: string[] = [];
    const applicationOrigin = new URL(baseURL!).origin;
    const isApplicationUrl = (url: string) => new URL(url).origin === applicationOrigin;

    page.on("pageerror", (error) => problems.push(`Uncaught exception: ${error.message}`));
    page.on("console", (message) => {
      if (message.type() === "error") {
        problems.push(`Console error: ${message.text()}`);
      }
    });
    page.on("requestfailed", (request) => {
      if (isApplicationUrl(request.url())) {
        problems.push(`Failed request: ${request.method()} ${request.url()} (${request.failure()?.errorText ?? "unknown error"})`);
      }
    });
    page.on("response", (response) => {
      const request = response.request();
      if (isApplicationUrl(response.url()) && checkedResourceTypes.has(request.resourceType()) && response.status() >= 400) {
        problems.push(`HTTP ${response.status()}: ${request.method()} ${response.url()}`);
      }
    });

    await use({ page });

    expect(problems, "the page should have no JavaScript, console, or resource-loading errors").toEqual([]);
  },
});

export { expect } from "@playwright/test";
