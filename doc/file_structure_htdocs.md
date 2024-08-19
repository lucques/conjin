# `htdocs` file structure
The following file structure is realized via Docker volumes. It is both present in the webserver container as well as the sync container.

```
- htdocs
                        (all from app's `src` dir)
    - content    
    - system
    - favicon.ico
    - modules           
    - modules-ext

                        (all from `conjin`, i.e. this repo)
    - conjin          
    - modules-shared
    - modules-shared-ext

                        (all from target dir)
    - .htaccess
    - config.json
    - modules-css           
    - modules-ext-css
    - modules-shared-css
    - modules-shared-ext-css

    - preprocess        (generated; only if hosted on external webserver)
```