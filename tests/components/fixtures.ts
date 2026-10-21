import { readFileSync } from 'node:fs';
import path from 'node:path';
import { test as base, expect } from '@playwright/test';

// The runner mounts source here; Playwright and its browser come from the image.
export const root = '/repo';

export const test = base.extend<{ componentHtml: Record<string, string>; feedbackHtml: string; pageErrors: void }>({
    componentHtml: async ({}, use) => {
        await use(JSON.parse(readFileSync(path.join(process.env.TEST_ARTIFACTS_DIR!, 'fixtures.json'), 'utf8')));
    },
    feedbackHtml: async ({ componentHtml }, use) => {
        await use(componentHtml['feedback-collector']);
    },
    pageErrors: [async ({ page }, use) => {
        const errors: string[] = [];
        page.on('pageerror', error => errors.push(error.message));
        await use();
        expect(errors).toEqual([]);
    }, { auto: true }],
});

export { expect };
