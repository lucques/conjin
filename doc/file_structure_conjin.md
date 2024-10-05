# Conjin repo file structure (this repo)
```
- build-tools                   Tools for building a deployment
- dhall                         Dhall types and tools
- doc                           Documentation
- docker                        Docker image for webserver
- ext
    - dhall                     Dhall libraries
    - modules-shared            Modules from external sources
- src
    - conjin                    Gets sym-linked into `htdocs/conjin`
    - modules-shared            Gets sym-linked into `htdocs/modules-shared`
    - htaccess_*                Contains parts of the root `.htaccess`
- composer.json                 Composer dependencies (populates `vendor` dir)
- composer.lock                 Composer lock file
```