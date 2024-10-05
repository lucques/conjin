# Start web server + DB server as virtualhost locally via nginx
There is one docker-dompose project
- Services with **autostart**:
    - Apache with PHP
    - Mariadb server
    - PHPmyadmin server
    - for each module where SCSS is activated (usually templates):
        - SCSS compiler (watch mode)
- Terminating services **without autostart**
    - Linkchecker app


## Get started
To deploy, the following four phases are needed.
1. Configuration.
    - Write a `config.dhall` file that contains an expression of type `LocalDepl`
        - Use the [/build-tools/build-deployment](../build-tools/generate-password-hash) `generate_password_hashes` tool
    - Integrate with your OS (needed for linkchecker to resolve the virtual host domain): Configure the virtual host in your OS in the `/etc/hosts` file, according to your setting in `config.dhall`, e.g.:
        ```
        127.0.0.1       my-project.localhost
        127.0.0.1       phpmyadmin.my-project.localhost
        ```
2. Build.
    - See also [Building](./build.md).
3. Run.
    - Run the `nginx-proxy` container (https://github.com/nginx-proxy/nginx-proxy).
    - Run one of the generated bin scripts.


## Executable scripts
The following scripts are generated in the `./target/bin` directory:
- `./target/bin/up`
    - Starts a docker compose project that includes:
        - Web server
        - MariaDB server (if configured)
        - PHPMyAdmin server (if configured)
        - SCSS watchers
    - After short delay: Calls `./target/bin/preprocess`
- `./target/bin/down`
    - Stops SCSS watchers and servers
- `./target/bin/preprocess`
    - Alternatively this can be run by launching `https://host/preprocess/`.
- `./target/bin/backup-db` (if configured)
    - Script that execs via the existing database container the `mysqldump` command.
- `./target/bin/backup-store` (if configured)
    - Script that execs via the existing webserver container some copy operations.
- `./target/bin/linkchecker`
    - Starts a docker compose project that runs a linkchecker service
    - The args given to this script are passed as the target id prefix to check.
    - E.g., `./target/bin/linkchecker first second` will check all URLs that start with `http://host/first/second/`.
    