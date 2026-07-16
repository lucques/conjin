import { test } from "@playwright/test";

import { expectProcessingFailure } from "../../../lib/expected-processing-failure";

test.describe("exercise", () => {
  test("rejects starting an exercise before ending the active one", async ({ request }) => {
    await expectProcessingFailure(
      request,
      "/module-tests-expect-fail/exercise/nested-start/",
      "Exercise not finished yet",
    );
  });

  for (const operation of ["item", "solution", "end"]) {
    test(`rejects ${operation} without an active exercise`, async ({ request }) => {
      await expectProcessingFailure(
        request,
        `/module-tests-expect-fail/exercise/${operation}-without-start/`,
        "No exercise started",
      );
    });
  }
});
