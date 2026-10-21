import { test } from "@playwright/test";

import { expectProcessingFailure } from "../../../../lib/expected-processing-failure";

test.describe("jsxgraph-helpers", () => {
  test("rejects zoom navigation without pan navigation", async ({ request }) => {
    await expectProcessingFailure(
      request,
      "/module-tests-expect-fail/jsxgraph-helpers/zoom-without-pan/",
      "cannot enable zoom navigation while pan is disabled",
    );
  });
});
