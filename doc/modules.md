# Modules
- Two independent flavors:
    - Either
        - **shared** (part of this repo) or
        - **local** (part of specific app)
    - Either
        - **external** (just linked from this repo, not included in it) or
        - **internal** (same license as this repo)
- Three possible roles that a module can take up, any combination possible:
    - **Preprocessing**
        - A module can hook into the preprocessing mechanism by implementing the `$init_preprocessing` function. This function then can:
            - Activate other modules
            - Create and add a preprocessor object to the preprocessing context. This is used e.g. to build up the navigation tree.
    - **Processing**
        - A module can hook into the processing mechanism by implementing the `$init_processing` function. This function gets access to the currently-processed target obj. This allows the definition of macros etc. that can then be used later by the `index.php` content files and template files.
    - **Templating**
        - A module may serve as a template. There are e.g. `header.php` and `footer.php` that both provide a function that renders some HTML for targets, while given access to the currently-processed target (see more below). There is also `login.php` to serve a login page.


- Module files and functions:
    - `preprocessing.php`
        - `init_preprocessing: PreprocessContext -> ()`
        - `create_preprocessor: PreprocessContext -> Preprocessor`
    - `processing.php`
        - `init_processing: Target -> ()`
            - Declares other processing modules that are required as runtime dependencies (cf. explanation further down)
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
