import { expect, test as setup } from "@playwright/test";

setup("preprocesses the integration fixture", async ({ request }) => {
  const baseURL = process.env.PREPROCESS_BASE_URL;
  const user = process.env.PREPROCESS_USER;

  expect(baseURL, "PREPROCESS_BASE_URL must be configured").toBeTruthy();
  expect(user, "PREPROCESS_USER must be configured").toBeTruthy();

  const response = await request.get(`${baseURL}/preprocess/`, {
    headers: { Cookie: `user=${user}` },
  });

  expect(response.status()).toBe(200);
});
