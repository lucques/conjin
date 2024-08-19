# Upload to external webserver via FTP
There is only one docker-compose project
- Services:
    - Sync via `rclone` tool
- Started manually, and then terminates

## Get started
To deploy, the following four phases are needed.
1. Configuration.
    - Install rclone (https://rclone.org/) and set up a remote SFTP host; give the remote a unique name. Identify the generated config file, usually `$HOME/.config/rclone/rclone.conf`.
    - Write a `config.dhall` file that contains an expression of type `DockerSyncDepl`
        - Use the [/build-tools/generate-password-hash](../build-tools/generate-password-hash) script
        - Store password of the preprocessing user via `secret-tool store --label='Password 1' password lookup_key1`, see also https://ict4g.net/adolfo/notes/admin/secret-tool.html
2. Build.
    - Run [/build-tools/build-deployment](../build-tools/build-deployment) script to build the deployment
    - This will build in `/target`:
        - Config files for docker-compose etc.
        - Bin files to run the app
        - Further dirs needed for deployment
    - This will not:
        - Preprocess
        - Render SCSS files
3. Run.
    - `./target/bin/sync`
        - Compile SCSS
        - Sync with remote server
        - After short delay: Call `./target/bin/preprocess`
    - `./target/bin/preprocess`: Alternatively this can be run by launching `https://host/preprocess/`.