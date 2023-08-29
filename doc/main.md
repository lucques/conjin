# Technical Documentation of Conjin

## Design Decisions (detailed version)
Some design decisions are taken as follows.
- Publishing new content should be extremely easy: Create new dir. Create `index.php`. Run `/preprocess/`. Done. No build steps.
- PHP
    - Advantages:
        - Easy deployment
        - Fast development cycle, no need to recompile, just reload the page 
        - Templating is built-in, everything is directed at producing output
        - Easy to write reusable macros
        - Well-established and large standard library
        - Good IDE support
        - In the future: Use additional external packages from Packagist
    - Disadvantages:
        - Not as modular as other languages. When including content files (`index.php`), template files (e.g. `header.php`) or module files (`module.php`), the scoping mechanism is not very elegant -- at these few points things have been crafted with great care.
- HTML & CSS
    - Complete control on page layout and styles
    - May also switch to Markdown on some sections and then generate HTML in the future
    - Good IDE support
    - Bootstrap CSS framework
- Docker
    - Easy deployment of orchestrated containers via docker-compose
- Dhall configuration language
    - Main selling point: Configuration files should be programmable (Mainly: DRY principle through the use of functions).
    - Declarative and statically typed: It is hard to misconfigure, good error messages
    - Possible to turn off static types for some sections by using the diret `JSON` embedding mechanism
    - Comments:
        - Disadvantage: Not well-established.
        - I wanted to try out the programmable-configuration approach to see how it works and I quite like Dhall although it is very verbose (type inference is given up to favor simplicity). Alternative to look into: CUE language
- LinkChecker
    - Find errors: Rendering failure, broken links, missing images etc. 
    - Comments:
        - Disadvantage: Slow, although multi-threading is used


## Deployment
Make sure to load all external modules in [../ext/](../ext/), most have a `build` script.

There are two predefined available deployment scenarios, both using Docker:

### 1. Start web server + DB server as virtualhost locally via nginx
There are two docker-dompose projects
- Web app
    - Services:
        - Apache with PHP
        - Mariadb server
        - PHPmyadmin server
        - for each template:
            - SCSS compiler (watch mode) 
    - Auto-start
- Linkchecker
    - Services:
        - Linkchecker app
    - Started manually, and then terminates

#### Get started
To deploy, the following four phases are needed.
1. Configuration.
    - Write a `config.dhall` file that contains an expression of type `DockerNginxDepl`
        - Use the [/build-tools/generate-password-hash](../build-tools/generate-password-hash) script
        - Store password of the preprocessing user via `secret-tool store --label='Password 1' password lookup_key1`, see also https://ict4g.net/adolfo/notes/admin/secret-tool.html
    - Integrate with your OS: Configure the virtual host in your OS in the `/etc/hosts` file, according to your setting in `config.dhall`, e.g.:
        ```
        127.0.0.1       my-project.localhost
        127.0.0.1       phpmyadmin.my-project.localhost
        ```
2. Build.
    - Run [/build-tools/build-deployment](../build-tools/build-deployment) script to build the deployment
    - This will build in `/target`:
        - Config files for docker-compose etc.
        - Bin files to run the app
        - Further dirs needed for deployment
    - This will not:
        - Preprocess
        - Render SCSS files
3. Run.
    - Run the `nginx-proxy` container (https://github.com/nginx-proxy/nginx-proxy).
    - Run one of the generated bin scripts.
        - `./target/bin/up`
            - Start SCSS watchers
            - Start servers
            - After short delay: Call `./target/bin/preprocess`
        - `./target/bin/linkchecker`: Run link-checker
        - `./target/bin/down`: Stop SCSS watchers and servers
        - `./target/bin/preprocess`: See below
4. Preprocess.
    - This is a PHP script on the server and gets called via `https://host/preprocess/` or alternatively via `./target/bin/preprocess`


### 2. Upload to external webserver via FTP
There is only one docker-compose project
- Services:
    - Sync via `rclone` tool
- Started manually, and then terminates

#### Get started
To deploy, the following four phases are needed.
1. Configuration.
    - Install rclone (https://rclone.org/) and set up a remote SFTP host; give the remote a unique name. Identify the generated config file, usually `$HOME/.config/rclone/rclone.conf`.
    - Write a `config.dhall` file that contains an expression of type `DockerSyncDepl`
        - Use the [/build-tools/generate-password-hash](../build-tools/generate-password-hash) script
        - Store password of the preprocessing user via `secret-tool store --label='Password 1' password lookup_key1`, see also https://ict4g.net/adolfo/notes/admin/secret-tool.html
2. Build.
    - Run [/build-tools/build-deployment](../build-tools/build-deployment) script to build the deployment
    - This will build in `/target`:
        - Config files for docker-compose etc.
        - Bin files to run the app
        - Further dirs needed for deployment
    - This will not:
        - Preprocess
        - Render SCSS files
3. Run.
    - Run one of the generated bin scripts.
        - `./target/bin/sync`
            - Compile SCSS
            - Sync with remote server
            - After short delay: Call `./target/bin/preprocess`
        - `./target/bin/preprocess`: See below
4. Preprocess.
    - This is a PHP script on the server and gets called via `https://host/preprocess/` or alternatively via `./target/bin/preprocess`


## Conjin repo file structure (this repo)
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


## Content repo file structure (part of your repo)
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


## `htdocs` file structure
The following file structure is realized via Docker volumes. It is both present in the webserver container as well as the sync container.
```
- htdocs
                        (all from app's `src` dir)
    - content    
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


## Definitions
- **Target**
    - External view: A webpage that can be accessed via URL and that contains content
    - Internal view: An `index.php` within the `content` dir tree
    - Each target is represented by a `Target` obj
- **Preprocessing** vs. **Processing**
    - Both phases are declared in the same `index.php` via the `$preprocess` and `$process` functions.
    - Preprocessing:
        - Main idea: Collect all "meta information" (target title etc.) to build a navigation etc.
        - Must be triggered explicitly
        - The whole content tree gets traversed and folded
        - Should be used also for time-intensive tasks like server-side rendering etc.
        - Not needed when just changing some content of a target
    - Processing:
        - Triggered on every target request
- **Module**
    - Two independent flavors:
        - Either
            - **shared** (part of this repo) or
            - **local** (part of specific app)
        - Either
            - **external** or
            - **internal** (same license as this repo)
    - Three possible roles that a module can take up, any combination possible:
        - **Preprocessing**
            - A module can hook into the preprocessing mechanism as follows: By activating other modules etc. (`$init_preprocessing` function), and through the creation of a preprocessor object (`$create_preprocessor` function) that gets access to the current `$PreprocessContext` obj. This is used e.g. to build up the navigation tree.
        - **Processing**
            - A module can hook into the processing mechanism by providing an initialization function that gets access to the currently-processed target (`$init_processing` function). This allows the definition of macros etc. that can then be used by the `index.php` content files and template files.
        - **Templating**
            - A module may serve as a template. There are e.g. `header.php` and `footer.php` that both provide a function that renders some HTML for targets, while given access to the currently-processed target (see more below). There is also `login.php` to serve a login page.
    - See section "Modules"
- **User**, **Group**, **Privilege**, **Action** -> See section "Authentication"
- **Path** vs. **URL**
    - Path: Internal file system, root points to `htdocs` folder path
    - URL:  Root points to `https://www.example.com/`


## Conventions
- Mode
    - A mode is a flag that can be set by the user when requesting a target, typically by adding the `?print=1` param to the URL.
    - Modes are always introduced by modules and not a built-in concept.
    - Examples: `print_mode` and `solution_mode`.
- IDs
    - Semantic ids:
        - Target ids, anchor ids, resource file names etc.
        - Lower case, separated by `-`
    - Technical ids, like `id` and `class` attribute in DOM:
        - Separate by `_` when semantic ids are involved, to avoid clashing with `-`


## Preprocessing
Preprocessing means to traverse the whole content tree, collecting meta data and caching it for ordinary processing.

It is triggered explicitly by calling `/preprocess/`

In PHP, the `preprocess_all: () -> Target` function in `preprocess_mech.php` handles the request. The resulting `Target` obj is a tree represenation of the whole content, with each node containing meta data such as title, activated modules and more. This obj is then saved to the cache.

The whole `content` directory tree is traversed top-down according to `add_subpage` calls. For each target file (i.e. `index.php`), there is one call to the **recursive** `preprocess: PreprocessingContext -> Target` function:
1. Start with a fresh `PreprocessingContext` obj.
    - Status: `CONSTRUCTED`.
2. Then inherited modules get initialized and the `Preprocessor` objs get spawned via `$create_preprocessor: ProcessingContext -> Preprocessor`.
    - Status: `INITIALIZED_INHERITED`
3. Include the `index.php` and retrieve the `$preprocess: PreprocessContext -> ()` function. Execute it.
    - Gets provided with the `PreprocessingContext` obj which offers:
        - Provides attributes to be set (e.g. `title`)
        - Preprocessor's functions can be called from within `index.php`
        - Preprocessing modules can be activated, causing the spawning of `Preprocessor` objs on-the-fly
        - Processing modules can be activated
        - If a template / preproc module / proc module gets inherited, then by default it gets inherited *transitively all the way down*
    - Afterwards, activate proc module dependencies of template
    - Status: `PASSED_THROUGH`
4. Initialize template module.
    - Status: `INITIALIZED_TEMPLATE`
5. Recurse on children (create child-context and run `preprocess`)
6. For each `Preprocessor` obj:
    - Execute its `finish: dict<string, PreprocessingContext> -> ()` function which gets provided the child-contexts.
    - Status: `FINISHED_PREPROCESSORS`
7. Eventually, there is a `Target` obj produced that contains all the content meta data.


## Processing (How ordinary requests are handled)
Processing means to take an HTTP request for a specific **target** and produce an adequate HTTP response.

### URLs

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

### Mechanism

In PHP, the `process: Target -> ()` function in `process_mech.php` handles the request.
1. The activated processing modules' `$init_processing: Target -> ()` functions get executed. This will include macros etc.
    - Runtime dependencies have been already resolved during preprocessing, so the topologically sorted list of processing modules is included one by one.
2. Include the `index.php` and retrieve the `$process: Target -> ()` function. Execute it.
3. Execute the `$process($target)` function.
4. Process the template header component via `process_template_component('header', $target);`
5. Process the template footer component via `process_template_component('footer', $target);`


## Modules
- Module files and functions:
    - `preprocessing.php`
        - `init_preprocessing: PreprocessContext -> ()`
        - `create_preprocessor: PreprocessContext -> Preprocessor`
    - `processing.php`
        - `get_processing_dependencies: () -> array<Module>`
            - Declares other processing modules that are required as runtime dependencies (cf. explanation further down)
        - `init_processing: Target -> ()`
            - Initializes the processing part of the module
        - Further functions offered to the processing phase
        - Module-specific state during processing phase should be managed by `$GLOBALS` var
    - `inc/*.php`
        - `render_for_target: (Module, Target) -> ()`
        - `render_generic:    (Module, Target, params: dict<string, mixed>) -> ()`
- *Module dir* contains, each of which is optional:
    ```
    - preprocessing.php 
    - processing.php
    - inc                  (for templating)
        - header.php
        - footer.php
        - login.php
    - scss                 (for templating: Fed into the SCSS compiler)
        - screen.scss      (example)
    - res                  (resources needed by the module)
    ```
- Often the components within a module work together: E.g. in the `nav` module,
    1. ... the preprocessor builds up a navigation tree (`NavItem`) and then...
    2. ... there is a function to render the navigation
- SCSS dependencies
    - A module may depend on other modules' SCSS dependencies.
    - E.g.: Dependence on Bootstrap source files (module by itself). Dependencies get loaded by the SCSS compiler.
    - These dependencies need to be declared in the Dhall config so that volumes can get loaded into the Docker containers
- Runtime dependencies
    - A processing module may depend on other processing modules 
    - A template module may depend on other processing modules
    - Of course the dependencies must not be be circular, therefore they span a DAG
    - These runtime dependencies are recursively resolved, going through the DAG in a topological order


## Authentication/Authorization
Auth consists of two components: Authentication (login, logout etc.) and authorization (who can view what).

### Authentication
On each request, authentication takes place (call to `auth_init()`). Afterwards, the `$GLOBALS['user']` contains the current user.

There are different methods to authenticate, they are tried in the following order:
1. Auth is disabled.
    - `$_GET['user']` is set         => User is as specified
    - `$_GET['user']` is not set     => User is `root`
2. Cookie. If a cookie `user` and `password` is sent, then for the current request that user/password combination is used for login.
    - User/password is correct       => User is as specified
    - User/password is incorrect     => User is `guest`
3. Login session. Gets stored in `$_SESSION['user']`
    - `$_SESSION['user']` is set     => User is as specified (resume session)
    - `$_SESSION['user']` is not set => User is `guest`

### Authorization
Authorization / rights management works according to the following principles:

- There are users, groups and privileges
    - User <-> Group <-> Privilege
    - Users and groups share an m:n relationship.
    - Groups and privileges share an m:n relationship.
- The following auth configuration is implicit:
    - For every user there is a group belonging to that user (`UserBased` group).
    - There are two built-in users, specified by `rootUser` and `guestUser`, typically `"root"` and `"guest"`.
    - The `rootUser` has all privileges
    - Any privilege of `guestUser` is also given to any other user.
- Further details:
    - A group is either `Custom` or `UserBased`, with different namespaces
    - A privilege is of a predefined set (currently only `Preprocess`) or a target-specific action.
    - An action is of a predefined set (currently only `View` -- view the target) or a custom action.
    - The `guestUser` is used when no other user is logged in, cannot login itself


## Ideas for future
- When it comes to anchors, there is currently only a processing component to them.
    - We could add a preprocessing compontent:
        - Preprocessing to build the nav
        - Processing to print the actual title
        - Idea: Integrate this by handling the "add-anchor-command" twice, but differently


## Not yet implemented
- Option `loginWithoutUserName` is ignored and always set to `true`