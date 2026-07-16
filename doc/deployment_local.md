# Start web server + DB server as virtualhost locally via nginx
There is one Docker Compose project.
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
To deploy, the following four phases are needed.
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
    - Run the `nginx-proxy` container (https://github.com/nginx-proxy/nginx-proxy).
    - Run one of the generated bin scripts.

## Internal job authentication

Local preprocessing and link checking do not retrieve or transmit static-user passwords. The webserver listens on port 80 for normal requests through nginx and on the unexposed container port 8080 for local deployment jobs. nginx is explicitly directed to port 80, while the `preprocess` service contacts `webserver:8080` and the linkchecker contacts the dotted network alias `webserver.internal:8080` directly through the Compose network. The dotted alias is required because LinkChecker's HTTP cookie library does not send domain cookies to single-label hostnames.

Apache marks requests received on port 8080 as internal, and the local Compose configuration separately enables internal authentication. Conjin trusts the `user` cookie only when both conditions are present and only when its value names a configured static user. Normal requests on port 80 continue to use password or OpenID authentication.

The `preprocessUser` and `linkchecker.user` configuration values select the identities used by the jobs. Both users must exist in `depl.authentication.staticUsers2passwords` and must receive the necessary authorization privileges. Their configured passwords are still available for normal interactive authentication but are not used by the local job scripts. `linkchecker.excludeTargets` contains configurable content target paths that LinkChecker should skip. The LinkChecker image always excludes the framework authentication endpoints `/login/` and `/logout/`; they are actions rather than configurable content targets.

Custom `LocalDepl` records must provide `preprocessUser`. The former local fields `desktopIntegration`, `linkcheckerPasswordLookupCmd`, and `linkcheckerPasswordRegisterCmd` have been removed; `desktopIntegration` remains part of remote deployment configuration.

Port 8080 is not published to the host, but it is reachable by other containers on the deployment's Compose network. Local deployment containers are therefore part of the trusted development environment. Remote deployments do not enable this mechanism.


## Executable scripts
The following scripts are generated in the `./target/bin` directory:
- `./target/bin/up`
    - Starts a docker compose project that includes:
        - Web server
        - MariaDB server (if configured)
        - PHPMyAdmin server (if configured)
        - SCSS watchers
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
    - Starts a docker compose project that runs a linkchecker service
    - Uses the internal local-job endpoint without retrieving a password.
    - The args given to this script are passed as the target id prefix to check.
    - E.g., `./target/bin/linkchecker first second` will check all URLs that start with `http://host/first/second/`.
