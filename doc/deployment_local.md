# Start web server + DB server as virtualhost locally via nginx

Each local deployment has one application Docker Compose project. All local deployments additionally share the Conjin reverse-proxy Compose project described below.
- Services with **autostart**:
    - Apache with PHP
    - Mariadb server
    - PHPmyadmin server
    - for each module where SCSS is activated (usually templates):
        - SCSS compiler (watch mode)
- Terminating services **without autostart**
    - Preprocessing app
    - Linkchecker app


## Get started
To deploy, the following three phases are needed.
1. Configuration.
    - Write a `config.dhall` file that contains an expression of type `LocalDepl`
        - Use `vendor/bin/depl generate-password-hashes <password-list.dhall> <output.dhall>` when password hashes must be generated in advance.
    - Integrate with your OS (needed for linkchecker to resolve the virtual host domain): Configure the virtual host in your OS in the `/etc/hosts` file, according to your setting in `config.dhall`, e.g.:
        ```
        127.0.0.1       my-project.localhost
        127.0.0.1       phpmyadmin.my-project.localhost
        ```
2. Build.
    - Follow the [deployment build](./deployment.md#building-a-deployment).
3. Run.
    - Start the shared Conjin reverse proxy once with `vendor/bin/depl proxy up`. This creates the persistent `conjin-proxy` Docker network and starts the version-pinned `nginx-proxy` service on it.
    - Run one of the generated bin scripts. Every generated local `bin/up` checks that the shared proxy is running and attached to the expected network before doing deployment work.

## Shared local reverse proxy

The reverse proxy is packaged with Conjin and shared by all local deployments so multiple projects can use distinct `*.localhost` hostnames while the proxy alone binds host ports 80 and 443. Manage it through the installed deployment command:

- `vendor/bin/depl proxy up` creates the persistent `conjin-proxy` network if necessary, starts the proxy, and waits for it to become healthy.
- `vendor/bin/depl proxy status` checks that the proxy container is running, healthy, and attached to `conjin-proxy`.
- `vendor/bin/depl proxy down` stops the proxy. The shared network remains available because local application containers may still be attached to it.

The proxy binds only to `127.0.0.1` and reads the Docker API socket to discover containers carrying `VIRTUAL_HOST` metadata. It is trusted local-development infrastructure and is not used by remote deployments. Stop local deployments before stopping the proxy when their sites should remain reachable.

## Internal job authentication

Local preprocessing and link checking do not retrieve or transmit static-user passwords. The webserver listens on port 80 for normal requests through nginx and on the unexposed container port 8080 for local deployment jobs. nginx is explicitly directed to port 80, while the `preprocess` service contacts `webserver:8080` and the linkchecker contacts the dotted network alias `webserver.internal:8080` directly through the Compose network. The dotted alias is required because LinkChecker's HTTP cookie library does not send domain cookies to single-label hostnames.

Apache marks requests received on port 8080 as internal, and the local Compose configuration separately enables internal authentication. Conjin trusts the `user` cookie only when both conditions are present and only when its value names a configured static user. Normal requests on port 80 continue to use password or OpenID authentication.

The `preprocessUser` and `linkchecker.user` configuration values select the identities used by the jobs. Both users must exist in `depl.authentication.staticUsers2passwords` and must receive the necessary authorization privileges. Their configured passwords are still available for normal interactive authentication but are not used by the local job scripts. `linkchecker.excludeTargets` contains configurable content target paths that LinkChecker should skip. The LinkChecker image always excludes the framework authentication endpoints `/login/` and `/logout/`; they are actions rather than configurable content targets.

Custom `LocalDepl` records must provide `preprocessUser`. The former local fields `desktopIntegration`, `linkcheckerPasswordLookupCmd`, and `linkcheckerPasswordRegisterCmd` have been removed; `desktopIntegration` remains part of remote deployment configuration.

Port 8080 is not published to the host, but it is reachable by other containers on the deployment's Compose network. Local deployment containers are therefore part of the trusted development environment. Remote deployments do not enable this mechanism.


## Executable scripts
The following scripts are generated in the `./target/bin` directory:
- `./target/bin/up`
    - Verifies that the shared Conjin reverse proxy is available.
    - Starts a docker compose project that includes:
        - Web server
        - MariaDB server (if configured)
        - PHPMyAdmin server (if configured)
        - SCSS watchers
    - Rebuilds selected services that define a Compose `build` configuration, using cached image layers when their inputs have not changed.
    - After Docker reports the configured services as running or healthy: Calls `./target/bin/preprocess`
- `./target/bin/down`
    - Stops SCSS watchers and servers
- `./target/bin/preprocess`
    - Uses the internal local-job endpoint without retrieving a password.
    - Alternatively this can be run through the configured virtual host at `/preprocess/`, using normal authentication.
- `./target/bin/reset-db` (if a database is configured)
    - Stops the deployment and deletes the current MariaDB data.
    - Starts the deployment again; MariaDB runs the configured initialization files, or creates a clean database server if no initialization files are configured.
    - Requires confirmation unless called with `--yes`.
- `./target/bin/reset-store` (if a store is configured)
    - Stops the webserver, deletes the current store contents, initializes the empty store, and starts the deployment again.
    - Requires confirmation unless called with `--yes`.
- `./target/bin/backup-db` (if configured)
    - Script that execs via the existing database container the `mysqldump` command.
- `./target/bin/backup-store` (if configured)
    - Script that execs via the existing webserver container some copy operations.
    - See [Store](./store.md) for store configuration and backup behavior.
- Error logging is configured separately and has no generated local backup command.
    - See [Error handling](./error_handling.md) for configuration and runtime behavior.
- `./target/bin/linkchecker`
    - Builds and runs the LinkChecker service, using cached image layers when its inputs have not changed.
    - Uses the internal local-job endpoint without retrieving a password.
    - The args given to this script are passed as the target id prefix to check.
    - E.g., `./target/bin/linkchecker first second` will check all URLs that start with `http://host/first/second/`.
