# Process (How ordinary requests are handled)
**Processing** means to take an HTTP request for a specific **target** or a **syslet** and produce an adequate HTTP response.


## Target URLs

1. All requests are forwarded by `.htaccess` to `index.php`
1. URL like `/page/subpage/subsubpage/res/my/res.jpg?user=myuser` gets parsed by `.htaccess`
2. Forward to `index.php` supplied with the following `$_GET` params:
    - `req`      = `process`
    - `target`   = `page/subpage/subsubpage`
    - `res`      = `my/res.jpg`
    - `user`     = `myuser`
3. If not authenticated, forward e.g. `/page/subpage/` to `/login/?redirect=/page/subpage/` with `$_GET` param:
    - `req`      = `login`
    - `redirect` = `/page/subpage/`


## Target mechanism

In PHP, the `process: Target -> ()` function in `process_mech.php` handles the request.
1. Each activated module's `processing.php` is loaded, and its optional `$process_target: (Module, Target) -> ()` function is executed. Loading the file also makes its macros available.
    - Runtime dependencies have been already resolved during preprocessing, so the topologically sorted list of processing modules is included one by one.
2. Load the target's `index.php` and execute its `$process: Target -> ()` closure, capturing the output.
3. Pass the captured content to the target's template for rendering.

Only targets with `inline` content are processed this way. The request handler follows `$redirect` for redirect targets and returns a not-found response for targets with no content. A target cannot define both `$process` and `$redirect`.

## Syslet mechanism

For login profiles, not-found pages, and unauthorized pages, each activated module's `processing.php` is loaded in dependency order and its optional `$process_syslet: (Module, Syslet, Target $target_root) -> ()` function is executed before template rendering. Login profiles are passed as `LoginProfile` objects, which extend `Syslet`.

Only the matching module entrypoint runs. Any common initialization is called explicitly or shared locally by the module; the framework does not call a separate common hook.
