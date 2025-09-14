# Store

The store is an optional directory for application-wide dynamic data that must
persist across requests and deployments. Typical contents are SQLite databases
and uploaded files.

It is a filesystem directory, not a key-value service or database abstraction.
Modules choose their own file formats and are responsible for reading, updating,
and validating their data.

Store data differs from generated files in the preprocess directory:

- The application may change store data while processing an HTTP request.
- Preprocessing may replace generated data in the preprocess directory.
- Deploying or uploading the application must not replace existing store data.


## Configuration and initialization

A deployment may be configured with or without a store. The generated
`config.json` contains `path_store` when a store is configured.

During application initialization, Conjin resolves `path_store` to an absolute
path. The directory must already exist; initialization fails if the configured
path cannot be resolved. Code that requires a store should therefore only be
used in a deployment where the store is configured.


## Accessing the store from PHP

Use the helpers in `src/conjin/inc/core_effectful.php`:

```php
$path = path_store('example.sqlite');
save_store_file('example.txt', 'Persistent content');
```

`path_store($suffix)` joins the suffix to the absolute path of the configured
store directory. A suffix must not start with `/`. The helper does not validate
or sanitize the suffix, so it must be a trusted relative path and must not
contain traversal such as `..`. Calling
`path_store()` without a suffix returns the store directory with a trailing
slash.

`save_store_file($filename, $content)` writes content to a store file. It also
creates missing parent directories. Code may use the path returned by
`path_store()` directly when another API manages the file. For example, the
issue tracker opens its database as follows:

```php
$this->db = new PDO('sqlite:' . path_store($this->config->file_name));
```

File names should be chosen so that modules do not overwrite one another's
data. A module that stores several files should use its own subdirectory.


## Local deployment

For a local deployment, `store` is an optional `LocalStore` value:

```dhall
{
, volDir : Text
, backupDir : Optional Text
}
```

`volDir` is mounted into the web-server container at `/files/store`. The
generated application configuration points `path_store` to this mount. The
build tooling can create the host directory and makes it writable by all users.

If `backupDir` is set, the build generates `target/bin/backup-store`. Running
that command copies the current store into a timestamped `snapshot-*`
subdirectory of `backupDir`.


## Remote deployment

A remote deployment configures the store as one of:

- `RemoteStore.None`
- `RemoteStore.WithoutBackup`
- `RemoteStore.WithBackup { backupDir = ... }`

When enabled, the store is `<pathBase>/store` on the remote server. Uploads use
`rclone sync` with `store/**` excluded. The store directory is created when
needed, but files already inside it are not uploaded, replaced, or deleted by a
deployment.

With `WithBackup`, the build generates `target/bin/backup-store`. This command
uses `rclone` to copy the remote store into a timestamped `snapshot-*` directory
below the configured local `backupDir`.

The deployment tooling provides only this remote-to-local backup mechanism. It
does not provide a corresponding command for uploading, restoring, or otherwise
seeding store files on the remote server. If a file such as an existing SQLite
database must be transferred into the remote store, that operation must be
performed manually. This is intentional: normal application uploads exclude
`store/**` so that a deployment cannot accidentally overwrite live runtime data.

In both deployment types, `backup-store` is a filesystem-level copy. It does not
pause the application or ask modules and databases to create a transactional
snapshot. Applications that write continuously must account for that when
deciding how and when to run backups.


## Inspecting store files

The debug page lists top-level store files when a store is configured. Selecting
a listed file displays its contents. Store files can contain private or
sensitive application data, so access to the debug page must be restricted
appropriately.
