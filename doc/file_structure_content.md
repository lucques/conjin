# Content repo file structure (part of your repo)
The following content file structure is kept very easy, but is strict.
```
- src
    - content
        - index.php                   Home page (specifies a target)
        - res                         Resources needed by `index.php`
        - first-subpage
            - index.php               First subpage (specifies a target)
            - res                     Resources needed by `index.php`
            ...
        - ...                         All further pages hav the same structure
    - system
        - login.php                   Specifies a syslet
        - not_found.php               Specifies a syslet
        - unauthorized.php            Specifies a syslet
    - modules                         (optional)
        - template-simple             (example)
            - processing_syslet.php   (optional) For processing a target
            - processing_target.php   (optional) For processing a syslet
            - preprocessing.php       (optional) For preprocessing
            - inc                     (optional) For template components
                - target.php          (example)
                - login.php           (example)
                - not_found.php       (example)
                - unauthorized.php    (example)
            - scss                    (optional) For templating: Fed to SCSS compiler
            - res                     (optional)
    - database-init                   (optional)
        - my-tables.sql
- ext
    - modules                         (optional) Modules from external sources
        - ...
```