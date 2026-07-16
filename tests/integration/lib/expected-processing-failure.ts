import { expect, type APIRequestContext } from "@playwright/test";

export async function expectProcessingFailure(
  request: APIRequestContext,
  url: string,
  expectedMessage: string,
): Promise<void> {
  const response = await request.get(url);

  expect(response.status()).toBe(500);
  expect(await response.text()).toContain(expectedMessage);
}
