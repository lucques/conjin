# Conjin repo file structure (this repo)
```
- build-tools                   Tools for building a deployment
- doc                           Documentation
- docker                        Docker image for webserver
- ext
    - modules-shared            Modules from external sources
- src
    - conjin                    Gets sym-linked into `htdocs/conjin`
    - modules-shared            Gets sym-linked into `htdocs/modules-shared`
    - htaccess_*                Contains parts of the root `.htaccess`
```