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
    - Run one of the generated bin scripts.


## Executable scripts
The following scripts are generated in the `./target/bin` directory:
- `./target/bin/upload`
    - Looks up the preprocessing password before compilation or upload, allowing the user to unlock the keychain immediately. A failed lookup or empty password stops the command.
    - Runs every configured Sass compiler to completion.
    - Uploads the deployment to the remote server using `rclone` only if every compilation succeeds.
    - Calls `./target/bin/preprocess` only if the upload succeeds.
- `./target/bin/upload-omit-sass`
    - Looks up the password, uploads and preprocesses like `upload`, but does not render Sass files.
- `./target/bin/preprocess`
    - Looks up its password when run directly. Upload commands keep the password in shell memory and pass it through stdin using `--password-stdin` after a successful upload, without another keychain lookup.
    - Alternatively this can be run by launching `https://host/preprocess/`.
- `./target/bin/backup-errorlog` (only if configured)
    - Uses `rclone` to copy the remote error log to a local backup location.
    - See [Error handling](./error_handling.md) for logging modes, defaults, and backup behavior.
- `./target/bin/backup-store` (only if configured)
    - Runs an `rclone` service in the same Compose project to copy the store directory to a backup location.
    - See [Store](./store.md) for store persistence during uploads and backup behavior.

## Content synchronization

Both `upload` and `upload-omit-sass` synchronize `src/content/` recursively, excluding every directory named exactly `dev` and its entire subtree at any depth. This includes `src/content/dev/`, target subdirectories such as `src/content/chapter/dev/`, and resource subdirectories such as `src/content/chapter/res/images/dev/`. Existing remote `dev/` directories below content are deleted, including those no longer present locally. Local files are left untouched.

The rule is case-sensitive and applies only to directories: `Dev/`, `development/`, `dev.txt`, and ordinary files named `dev` are synchronized normally. Content has its own sync step with deletion of excluded files enabled; paths outside content retain their existing upload behavior.


## Private code and runtime directories

`makeDefaultRemoteDepl` supplies `internalDir = "conjin"`. Configurations constructing a `RemoteDepl` record directly must provide that field. It identifies the private deployment-managed directory containing the framework, application implementations, modules, `config.json`, and `users.json`. Its siblings `<internalDir>-dyn-preprocessing` and `<internalDir>-dyn-data` hold preprocessing output and persistent data respectively. All three directories have `.htaccess` protection denying HTTP access. See [the full filesystem layout](./file_structure_htdocs.md).

To customize the names, apply `with internalDir = "my-conjin"` to the helper's result. This selects `my-conjin`, `my-conjin-dyn-preprocessing`, and `my-conjin-dyn-data`. The name must start with a letter or digit and contain only letters, digits, underscores, or hyphens. The code, preprocessing, staging (`-dyn-preprocessing-new`), previous (`-dyn-preprocessing-old`), and persistent-data URL prefixes are reserved and cannot overlap configured static-file destinations. Existing configs with an explicit `internalDir` keep that code-directory name; configs using the helper's default adopt `conjin`.

The public `index.php` and `module-resource.php` files are small generated bootstraps that load private implementations. Existing page, login, preprocessing, and module resource URLs stay unchanged. `config.json` and `users.json` are no longer uploaded to the public root.

The bootstraps change PHP's working directory to `<internalDir>/framework`. Generated filesystem paths are relative to that directory: `path_base` is `..`, preprocessing is `../../<internalDir>-dyn-preprocessing`, and persistent data is under `../../<internalDir>-dyn-data`. These runtime paths follow the directory layout directly and do not depend on `pathBase`.

Uploads replace code with one `rclone sync` pass over non-content files, followed by the separate content sync described above. Generated preprocessing output is excluded and preserved. There is no preliminary copy pass. The non-content sync creates empty source directories and excludes the entire persistent data tree. Its access-protection file is uploaded separately, and configured store and error-log directories are created before synchronization. Configured backup commands read the new persistent paths.

When changing `internalDir`, move existing store and log data to the corresponding persistent data directory before uploading.

Preprocessing owns `<internalDir>-dyn-preprocessing` and its `-new` and `-old` siblings. Upload excludes all of them from synchronization and deletion. PHP needs permission to create and rename directories in their common parent, in addition to writing generated output. See [the preprocessing lifecycle](preprocess.md#output-publication).
