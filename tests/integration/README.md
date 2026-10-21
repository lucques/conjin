# Deployed HTTP and browser tests

This npm workspace uses Playwright in Docker. `specs/http/` runs HTTP contracts under the `http` project; `specs/e2e/` runs deployed browser behavior under `chromium`. Both trees retain successful and expected-error fixture branches. Preparation installs locked dependencies in the image, which includes Chromium and its system dependencies. Component tests reuse this image and Compose service with their own fixtures and configuration.

```sh
bin/test prepare e2e-playwright
bin/test integration-http
bin/test e2e-playwright
bin/test e2e-playwright /expect-success/module-tests/anchors.spec.ts
bin/test integration-linkchecker module-tests-expect-success/db-mysql/query
```

Both suites use the same prepared integration environment. The runner preprocesses once through the internal endpoint and reuses the integration deployment across HTTP, browser and LinkChecker suites in an `all` run. Direct Playwright invocation can use the setup dependency by providing `PREPROCESS_BASE_URL` and `PREPROCESS_USER`.

Reports replace root `test-artifacts/` on each invocation. The two Playwright suites write `integration-http/` and `e2e-playwright/`, with individual results included in root `summary.json` and `junit.xml`. See [tests](../../doc/tests.md) and the [coverage map](../../doc/test_inventory.md) for classification, requirements and limitations. Isolated component browser tests live in `tests/components/`.
