import { test } from "@playwright/test";

import { expectProcessingFailure } from "../../../lib/expected-processing-failure";

test.describe("anchors", () => {
  test("rejects levels below two", async ({ request }) => {
    await expectProcessingFailure(
      request,
      "/module-tests-expect-fail/anchors/level-too-low/",
      "Level 1 must be at least 2",
    );
  });

  test("rejects skipped levels", async ({ request }) => {
    await expectProcessingFailure(
      request,
      "/module-tests-expect-fail/anchors/levels-inconsistent/",
      "Level 4 exceeds number of possible parent anchors",
    );
  });

  test("rejects duplicate IDs below the same parent", async ({ request }) => {
    await expectProcessingFailure(
      request,
      "/module-tests-expect-fail/anchors/duplicate-id/",
      "Anchor with id 'duplicate' already exists",
    );
  });
});
