# Deployed filesystem layout

The three private directories have different owners and deployment lifecycles:

| Directory | Contents | Deployment behavior | PHP access |
|---|---|---|---|
| `conjin` | Framework, application code, configuration, module assets | Replaced from deployment sources | Read |
| `conjin-dyn-preprocessing` | Generated preprocessing objects and resources | Replaced only after successful generation | Read/write, including parent |
| `conjin-dyn-data` | Persistent store and logs | Preserved by uploads | Read/write |

Remote deployments place these directories inside DocumentRoot to accommodate shared hosts:

```text
htdocs/
    .htaccess
    index.php                   # Generated public bootstrap
    module-resource.php         # Generated public bootstrap
    ...                         # Explicitly configured public static files
    conjin/
        .htaccess               # Require all denied
        config.json
        users.json
        framework/
        content/
        system/
        vendor/
        modules*/               # Configured source modules and compiled assets
    conjin-dyn-preprocessing/
        .htaccess               # Require all denied; survives uploads
        obj/
        res/
    conjin-dyn-data/
        .htaccess               # Require all denied
        store/                  # Optional
        logs/error/             # Optional
```

The remote `internalDir` setting defaults to `conjin`. The two dynamic names derive from it by appending `-dyn-preprocessing` and `-dyn-data`; no extra configuration fields are needed. Local and test containers keep private files outside DocumentRoot. Their preprocessing volume mounts at `/files/conjin-preprocessing-runtime`, with current output in its `conjin-dyn-preprocessing` child; staging and previous output are sibling children. Other private directories remain beneath `/files`, alongside `/files/htdocs`. Existing local host volume paths and test volume names stay unchanged.

Only the two generated bootstraps and configured static files are public. Each bootstrap changes into the private `framework` directory and loads its implementation there. Configuration and password hashes are read from the private code directory. There are no public framework entrypoints or configuration files inside the private directories and no access-control exceptions for them.

Public page and resource URLs stay unchanged. Target resources pass through authorization. Module resource URLs are routed to the public `module-resource.php` bootstrap: source modules expose `res/`, while compiled directories also expose `.css` and `.css.map` files at their existing paths. Traversal and implementation-file checks remain in the private handler.

The generated `config.json` field `path_base` is the private code root used by PHP's `path()` helper. The remote Dhall setting `pathBase` still specifies the deployed web-root directory. Public URLs continue to use `url_base` and `url()`.
