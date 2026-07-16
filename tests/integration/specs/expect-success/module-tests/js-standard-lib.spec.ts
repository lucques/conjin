import { expect, test } from "../../../fixtures/page-health";

test("generates different IDs", async ({ healthyPage }) => {
  const { page } = healthyPage;
  const response = await page.goto("/module-tests-expect-success/js-standard-lib/");

  expect(response?.ok()).toBe(true);
  const [firstId, secondId] = await page.evaluate(() => [
    nextUniqueId(),
    nextUniqueId(),
  ]);

  expect(firstId).not.toBe(secondId);
});

declare global {
  function nextUniqueId(): string;
}
