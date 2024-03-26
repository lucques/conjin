# Content repo file structure (part of your repo)
The following content file structure is kept very easy, but is strict.
```
- src
    - content
        - index.php             Home page
        - res                   Resources needed by `index.php`
        - ...                   Each subdir has the same structure
    - modules                   (optional)
        - my-template           (example)
            - processing.php    (optional) For processing
            - preprocessing.php (optional) For preprocessing
            - inc               (optional) For template processing
                - header.php    (example)
                - footer.php    (example)
                - login.php     (example)
            - scss              (optional) For templating: Fed to SCSS compiler
            - res               (optional)
    - database-init             (optional)
        - my-tables.sql
- ext
    - modules                   (optional) Modules from external sources
        - ...
```