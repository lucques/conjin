# Integration tests

The integration-test package is an npm workspace managed by the repository root. Run the root-level `npm ci` (or `./setup.sh`) to install its editor dependencies. The Docker test image continues to use the integration package's own lockfile.

Run `./tests/run all` to execute `demo-linkchecker`, `integration-linkchecker`, and `integration-playwright` in that order. The repository-level runner builds each app's single `deployments/test` deployment, prepares isolated Compose state, and invokes only the requested capability. In `all` mode, each target gets an independent attempt; later targets still run after a failure, and the final command status reports whether any target failed.

Run LinkChecker at the application root or at a specific target:

```sh
./tests/run demo-linkchecker
./tests/run demo-linkchecker bootstrap-alternative
./tests/run integration-linkchecker
./tests/run integration-linkchecker module-tests-expect-success/db-mysql/query
```

Run all Playwright tests or select a path below `tests/integration/specs/`:

```sh
./tests/run integration-playwright
./tests/run integration-playwright /expect-fail
./tests/run integration-playwright /expect-success/module-tests/anchors.spec.ts
```

For focused local reruns, pass `--skip-build` to reuse an existing generated test deployment:

```sh
./tests/run integration-playwright /expect-success/module-tests/anchors.spec.ts --skip-build
```

This skips only test-deployment generation. SCSS compilation, Docker service builds, preprocessing, isolated volumes, and cleanup still run. Run without the flag after changing deployment configuration or generation code.

The integration deployment's LinkChecker configuration excludes `module-tests-expect-fail`; Playwright can access both `expect-success` and `expect-fail`. The Playwright setup project preprocesses the fixture before selected specs run.

Read-only repository inputs are bind-mounted. Database files, preprocessing output, compiled CSS, and the optional application store use project-scoped named volumes that are removed after the run. Each invocation creates a directory such as `tests/artifacts/2026-07-23.12-23/`; LinkChecker results are stored in `linkchecker/` and Playwright results in `playwright/`.

Set `CONJIN_TEST_PROJECT` to choose the Compose project-name prefix or `CONJIN_TEST_KEEP=1` to keep stacks for inspection.
