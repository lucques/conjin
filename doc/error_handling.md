# Error handling

Conjin converts PHP errors into HTTP 500 responses and can write them to
rotating log files. Error display and file logging are configured separately.


## Runtime behavior

During initialization, Conjin registers an error handler and a shutdown
handler. An error passed to `fail()` is handled as follows:

1. The error is sent to the `error` logger.
2. The response status is set to HTTP 500.
3. The response contains a generic error message.
4. If `errorlog_display` is enabled, the detailed error is appended to the
   response.

If an error-log directory is configured, the logger uses Monolog's
`RotatingFileHandler` and includes web-request information and the current user
in each record. Code that needs to record an error without terminating the
request can call `log_error_silently($message)`.

Detailed errors can contain stack traces, filesystem paths, and other internal
information. `errorlog_display` should normally be disabled in production.


## Generated application configuration

The deployment build converts the Dhall error configuration into two fields in
the generated `config.json`:

```json
{
  "errorlog_display": false,
  "errorlog_dir": "/path/to/logs/error"
}
```

`errorlog_display` determines whether details appear in the HTTP response.
`errorlog_dir` determines whether file logging is enabled and where the log
files are written. When no directory is configured, errors are still converted
to HTTP 500 responses but are not persisted by the file logger.

The configured directory must exist and be writable by the web-server process.
Application initialization fails if `errorlog_dir` is present but cannot be
resolved.


## Local deployment

A local deployment uses this configuration type:

```dhall
let LocalErrors =
    { display : Bool
    , logToVolDir : Optional Text
    }
```

For example:

```dhall
{ display = True
, logToVolDir = Some "/local/docker-volumes/logs/error"
}
```

When `logToVolDir` is set, that host directory is mounted into the web-server
container at `/files/logs/error`, and the generated `errorlog_dir` points to the
mount. The build tooling can create the directory and make it writable.

The default local configuration is intended for development:

```dhall
{ display = True
, logToVolDir = None Text
}
```

There is currently no generated `backup-errorlog` command for local
deployments. A configured `logToVolDir` is already a persistent host directory
and can be backed up using the host's normal backup mechanism.


## Remote deployment

A remote deployment separates display from the logging and backup mode:

```dhall
let RemoteErrors =
    { display : Bool
    , logging : RemoteErrorLogging
    }

let RemoteErrorLogging =
    < None : {}
    | WithoutBackup : {}
    | WithBackup : { backupDir : Text }
    >
```

For example:

```dhall
{ display = False
, logging =
    T.RemoteErrorLogging.WithBackup
      { backupDir = "/local/backups/logs/error" }
}
```

The logging modes have the following effects:

| Mode | Remote log directory | `backup-errorlog` |
|---|---|---|
| `None` | Not configured | Not generated |
| `WithoutBackup` | `<pathBase>/logs/error` | Not generated |
| `WithBackup { backupDir }` | `<pathBase>/logs/error` | Generated |

The default remote configuration is production-oriented: detailed errors are
not displayed, file logging is enabled, and backups are enabled. Its backup
destination is the default backup directory below
`<deployment-directory>/logs/error`.

Remote uploads exclude `logs/error/**` from `rclone sync`. An upload creates the
directory when necessary but does not replace or delete existing log files.

With `WithBackup`, running `target/bin/backup-errorlog` uses `rclone` to copy
the remote `logs/error` directory into a timestamped `snapshot-*` directory
below `backupDir`. The configured `backupDir` is local to the machine running
the deployment command; it is not a path on the remote web server.


## Store and error logs

The store and error-log directory use similar deployment and backup mechanisms,
but they are independent:

- The store contains dynamic application data such as SQLite databases and
  uploaded files.
- The error-log directory contains diagnostic records written by the logger.
- `backup-store` and `backup-errorlog` are configured and run separately.

See [Store](./store.md) for store configuration and persistence.

