# Conjin repo file structure (this repo)
```
- build-tools                   Tools for building a deployment
- docker                        Docker image for webserver
- src
    - conjin                    Gets sym-linked into `htdocs/conjin`
    - modules-shared            Gets sym-linked into `htdocs/modules-shared`
    - htaccess_*                Contains parts of the root `.htaccess`
- ext
    - modules-shared            Modules from external sources
- doc                           Documentation
```