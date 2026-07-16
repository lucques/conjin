import { test } from "@playwright/test";

import { expectProcessingFailure } from "../../../lib/expected-processing-failure";

test.describe("issue tracker", () => {
  test("rejects a negative own-content edit window", async ({ request }) => {
    await expectProcessingFailure(
      request,
      "/module-tests-expect-fail/issue-tracker/negative-edit-window/",
      "may_edit_own_content_within_n_minutes must not be negative",
    );
  });
});
