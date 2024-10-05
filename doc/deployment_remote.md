# Upload to external webserver via FTP
There is only one docker-compose project
- Services:
    - Sync via `rclone` tool
- Started manually, and then terminates

## Get started
To deploy, the following four phases are needed.
1. Configuration.
    - Install rclone (https://rclone.org/) and set up a remote SFTP host; give the remote a unique name. Identify the generated config file, usually `$HOME/.config/rclone/rclone.conf`.
    - Write a `config.dhall` file that contains an expression of type `RemoteDepl`
        - Use the [/build-tools/build-deployment](../build-tools/generate-password-hash) `generate_password_hashes` tool
        - Store password of the preprocessing user via `secret-tool store --label='Password 1' password lookup_key1`, see also https://ict4g.net/adolfo/notes/admin/secret-tool.html
2. Build.
    - See also [Building](./build.md).
3. Run.
    - Run the `nginx-proxy` container (https://github.com/nginx-proxy/nginx-proxy).
    - Run one of the generated bin scripts.


## Executable scripts
The following scripts are generated in the `./target/bin` directory:
- `./target/bin/upload`
    - Starts a docker compose project that includes:
        - SCSS compiler
        - Upload deployment to remote server using `rclone`
    - After short delay, calls `./target/bin/preprocess`
- `./target/bin/upload-omit-sass`
    - Just like `upload` but without rendering SASS files
- `./target/bin/preprocess`
    - Alternatively this can be run by launching `https://host/preprocess/`.
- `./target/bin/backup-store` (only if configured)
    - Starts a docker compose project that includes a single service:
        - Runs an `rclone` command to copy the store directory to a backup location
                    - `bin/backup-errorlog`: Backups error log from remote server
            - `bin/backup-store`: Backups store from remote server
