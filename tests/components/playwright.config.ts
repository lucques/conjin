import path from 'node:path';
import { defineConfig, devices } from '@playwright/test';

const artifacts = process.env.TEST_ARTIFACTS_DIR ?? path.resolve(__dirname, '../../test-artifacts/component-playwright');

export default defineConfig({
    testDir: '.',
    testMatch: '*.spec.ts',
    fullyParallel: true,
    forbidOnly: Boolean(process.env.CI),
    retries: process.env.CI ? 1 : 0,
    workers: process.env.CI ? 2 : undefined,
    timeout: 60000,
    outputDir: path.join(artifacts, 'test-results'),
    reporter: [
        ['json', { outputFile: path.join(artifacts, 'results.json') }],
        ['line'],
        ['html', { outputFolder: path.join(artifacts, 'playwright-report'), open: 'never' }],
        ['junit', { outputFile: path.join(artifacts, 'junit.xml') }],
    ],
    projects: [{ name: 'components', use: { ...devices['Desktop Chrome'] } }],
    use: {
        channel: 'chromium',
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
        launchOptions: { args: ['--no-sandbox'] },
    },
});
