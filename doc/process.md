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
1. The activated processing modules' `$init_processing: Target -> ()` functions get executed. This will include macros etc.
    - Runtime dependencies have been already resolved during preprocessing, so the topologically sorted list of processing modules is included one by one.
2. Depending on whether the content location is `inline` or `extra`:
    1. `inline`: Include the `index.php` and execute the `process: Target -> ()` function.
    2. `extra`: Include the `content.php` (no function to execute)