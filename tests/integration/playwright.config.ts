import path from "node:path";
import { defineConfig, devices } from "@playwright/test";

const artifactsDir = process.env.TEST_ARTIFACTS_DIR ?? path.resolve(__dirname, "..", "artifacts");

export default defineConfig({
  testDir: ".",
  testMatch: ["specs/**/*.spec.ts", "setup/**/*.setup.ts"],
  fullyParallel: true,
  forbidOnly: Boolean(process.env.CI),
  retries: process.env.CI ? 1 : 0,
  workers: process.env.CI ? 2 : undefined,
  outputDir: path.join(artifactsDir, "test-results"),
  reporter: [
    ["line"],
    ["html", { outputFolder: path.join(artifactsDir, "playwright-report"), open: "never" }],
    ["junit", { outputFile: path.join(artifactsDir, "junit.xml") }],
  ],
  use: {
    baseURL: process.env.TEST_BASE_URL ?? "http://webserver",
    screenshot: "only-on-failure",
    trace: "retain-on-failure",
  },
  projects: [
    {
      name: "setup",
      testMatch: "setup/**/*.setup.ts",
    },
    {
      name: "chromium",
      dependencies: ["setup"],
      testMatch: "specs/**/*.spec.ts",
      use: {
        ...devices["Desktop Chrome"],
        launchOptions: { args: ["--no-sandbox"] },
      },
    },
  ],
});
