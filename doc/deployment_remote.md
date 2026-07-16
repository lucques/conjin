# Upload to external webserver via FTP

There is one Docker Compose project, defined by `target/docker-compose.yml`. Its Sass compilation, rclone upload, and optional rclone backup services are one-shot jobs that are started manually and removed after they terminate.

## Get started
To deploy, the following four phases are needed.
1. Configuration.
    - Install rclone (https://rclone.org/) and set up a remote SFTP host; give the remote a unique name. Identify the generated config file, usually `$HOME/.config/rclone/rclone.conf`.
    - Write a `config.dhall` file that contains an expression of type `RemoteDepl`
        - Use `vendor/bin/depl generate-password-hashes <password-list.dhall> <output.dhall>` when password hashes must be generated in advance.
        - Store password of the preprocessing user via `secret-tool store --label='Password 1' password lookup_key1`, see also https://ict4g.net/adolfo/notes/admin/secret-tool.html
2. Build.
    - Follow the [deployment build](./deployment.md#building-a-deployment).
3. Run.
    - Run the `nginx-proxy` container (https://github.com/nginx-proxy/nginx-proxy).
    - Run one of the generated bin scripts.


## Executable scripts
The following scripts are generated in the `./target/bin` directory:
- `./target/bin/upload`
    - Runs every configured Sass compiler to completion.
    - Uploads the deployment to the remote server using `rclone` only if every compilation succeeds.
    - Calls `./target/bin/preprocess` only if the upload succeeds.
- `./target/bin/upload-omit-sass`
    - Uploads and preprocesses like `upload`, but does not render Sass files.
- `./target/bin/preprocess`
    - Alternatively this can be run by launching `https://host/preprocess/`.
- `./target/bin/backup-errorlog` (only if configured)
    - Uses `rclone` to copy the remote error log to a local backup location.
    - See [Error handling](./error_handling.md) for logging modes, defaults, and backup behavior.
- `./target/bin/backup-store` (only if configured)
    - Runs an `rclone` service in the same Compose project to copy the store directory to a backup location.
    - See [Store](./store.md) for store persistence during uploads and backup behavior.
