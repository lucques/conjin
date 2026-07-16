# Conjin integration-test app

This application is a small, deterministic fixture for the repository's Docker-based Playwright and LinkChecker integration tests. Its pages exist to exercise exact framework contracts; human-facing examples belong in the sibling demo app.

Run `./setup.sh` from the repository root before running the repository test targets. The generated test deployment uses isolated Compose volumes and is written below `deployments/test/target`.

## Local browser deployment

The persistent `conjin-test-web` deployment makes the fixture available for manual inspection in a browser. Build and start the deployment:

```sh
./composer-projects/integration-test-app/deployments/conjin-test-web/build
./composer-projects/integration-test-app/deployments/conjin-test-web/target/bin/up
```

Open `http://conjin-test-web.localhost/`. The positive anchors fixture is available at `http://conjin-test-web.localhost/module-tests-expect-success/anchors/paths/`.

Runtime PHP and content changes are bind-mounted and appear on refresh. Run `deployments/conjin-test-web/target/bin/preprocess` after changes that affect preprocessing, module activation, or navigation. Use `deployments/conjin-test-web/target/bin/down` to stop the deployment.

## Expected processing failures

The `module-tests-expect-fail` target contains subtargets that intentionally fail during the processing phase. The branch and all descendants must preprocess successfully; preprocessing failures require a separate fixture.

The contentless grouping directories use the app's default target, which activates `subpages-all` but not `nav-build`. Preprocessing therefore discovers the descendants while the branch stays outside generated navigation and the LinkChecker crawl. Playwright requests these targets directly and verifies their stable failure contracts.

Keep one focused target per failure case. Expected-failure specs belong in `tests/integration/specs/expect-fail/`, and shared assertions belong in `tests/integration/lib/`.
