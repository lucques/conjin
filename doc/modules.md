# Modules
- Origin is categorized in two dimensions:
    - Either
        - **shared** (part of this repo) or
        - **local** (part of specific app)
    - Either
        - **external** (just linked from this repo, not included in it) or
        - **internal** (same license as this repo)
- Distinction between **module** and **module location**:
    - **Module location** does not have a config
    - **Module** has a config, namely a `ConfigTree` obj which is a wrapper for an assoc array
- A module is attached to a **target** or a **syslet**.
- Three possible functions that a module can take up, any combination possible:
    - **Preprocessing**
        A module can hook into the preprocessing mechanism by implementing the `$init_preprocessing`, `$init_preprocessing_target`, `$init_preprocessing_syslet` functions. These functions then can:
        - Activate and configure other modules
        - Create and add a preprocessor object to the preprocessing context. This is used e.g. by `nav-build` to build up the navigation tree.
        - Provide preprocessing macros to be used by other modules during the preprocessing phase.
    - **Processing**
        - A module can hook into the processing mechanism by implementing the `$init_processing`, `$init_processing_target`, `$init_processing_syslet` functions. These functions get access to the currently-processed target / syslet obj. This allows the definition of macros etc. that can then be used later by the `index.php` content files, other modules and template files.
    - **Templating**
        - A module may serve as a template. There are e.g. `target.php`, `login.php` that both provide a function that renders output for processables, while given access to the currently-processed processable (see more below).


## Files and Functions:
- `default_config.php`
    - `default_config: () -> dict<string, mixed>`
    - Returns the default config of the module; must be complete
- `preprocessing.php`
    - Always included; therefore Preprocessing macros (= top level PHP functions) can be defined here
    - `init_*` defs:
        - `init_preprocessing:        (Module, PreprocessContext)       -> ()` always called
        - `init_preprocessing_target: (Module, TargetPreprocessContext) -> ()` only called for target
        - `init_preprocessing_syslet: (Module, SysletPreprocessContext) -> ()` only called for syslet
        - May declare module dependencies by calling `$c->activate_module` etc. Results in a DAG of activated modules.
- `processing.php`
    - Always included; therefore: Processing macros (= top level PHP functions) can be defined here
    - `init_*` defs:
        - `init_processing:           (Module) -> ()`                 always called
        - `init_processing_target:    (Module, Target $target) -> ()` only called for target
        - `init_processing_syslet:    (Module, Syslet $syslet) -> ()` only called for target
        - May init state that is needed by this module during the processing phase, e.g. a db connection. Such state should be managed by `$GLOBALS` var
- `templates/`:
    - `target.php`
        - `render_target: (ModuleLocation, Module, Target, $content: string, $placeholders_override: dict<string, mixed>) -> ()`
    - `login.php`
        - `render_login: (ModuleLocation, Module, Syslet, $logout_sucessful: bool, $password_incorrect: bool, $openid_fail: mixed, $openid_provider_names: array, $placeholders_override: dict<string, mixed>) -> ()`
    - `notfound.php`
        - `render_not_found: (ModuleLocation, Module, Syslet, $target_ids: ?array, $placeholders_override: dict<string, mixed>) -> ()`
    - `unauthorized.php`
        - `render_unauthorized: (ModuleLocation, Module, Syslet, $target_ids: ?array, $placeholders_override: dict<string, mixed>) -> ()`
        - TODO: This is not implemented yet
- `scss/`:
    - Contains SCSS files that are fed into the SCSS compiler
- `res/`:
    - Contains resources needed by the module


## Dependencies    
- Declaration via calling `activate_module` during `$init_preprocessing`
    - These calls form a DAG (must not be circular therefore)
    - These calls are collected in a topological order and saved so that it is very easy during the later processing phase to init all modules in a correct order
- SCSS dependencies
    - A module may depend on other modules' SCSS dependencies.
    - E.g.: Dependence on Bootstrap source files (module by itself). Dependencies get loaded by the SCSS compiler.
    - These dependencies need to be declared in the Dhall config so that volumes can get loaded into the Docker containers


## Templates
A template *is* a module. Therefore it gets configured like all other modules, too.

Two layers serve different purposes:
- A call to `Module::render_target`, `Module::render_login`, ... starts the rendering process, provided...
    - a **processable** (e.g. the target to be rendered)
    - parameters
    - `placeholders_overrides` (explanation follows; only used when extending a base template)
- This will cause the `$render_target`, `$render_login`, ... of `target.php`, `login.php`, ... to be called. They are provideded...
    - the current module location
    - a **template** (module). Note that this can be a derived template, e.g. `template-interbook` is based on `template-navigable`
    - the current **processable**
    - parameters
    - `placeholders_overrides`
    Now these functions...
    1. Generate `placeholders_default`: These are low-level "values-to-print" into the HTML later
    2. Merge those `placeholders_default` with `placeholders_overrides`
        - This allows a template to be extended -- just override some placeholders
    3. Render
- The placeholders are an intermediate layer to provide the overriding mechanism

What often happens is that templates are based on each other, e.g. `template-interbook` builds upon `template-navigable`. Some comments on this:
- The config of the derived template must extend the config of the base template.


## Conventions
- "A **depends on** B"
    - We do not declare all modules in every `index.php` file but use the following dependency mechanism to package the modules together: During the preprocessing phase, a module can call `$c->activate_module` to declare that it depends on another module. This results in a DAG of activated modules.
- "A **supports** B"
    - Module A **supports** module B if A integrates B in the way that B described in its specs. Note that there is no dependency here.
    - E.g.: The `template-interbook` module supports `sol-mode` by providing the show-solution-toggle-button as demanded.
- **Template module**: Modules of the name `template-<name>`
    - We declare the modules which are strictly associated with a template as dependencies of that template.
        - E.g., the `nav` module is a dependency of the `template-interbook` module, because it is needed to render the sidebar.
- **Role module**: Modules of the name `role-<name>`
    - This is a module whose only purpose is to group together modules for a specific kind of page, called a role.
    - E.g., the `role-content` module groups together all modules that are needed to render an information/content page.
    - E.g., the `role-exercise` module groups together all modules that are needed to render an exercise page. 
- Module named `local`: Contains local resources like logo etc.



## Conventions for Templates
1. One CSS file per media type
    - Typically there is one CSS file generated by media type, usually:
        - `screen.css`
        - `print.css`
2. SCSS files may be further split up
    - For each template, SCSS files are already split up sometimes. The following conventions are taken.
        - `screen.scss` always exists
        - `print.scss` always exists
        - Any other imported SCSS file is located in a folder `partials`
            - `_common.scss` is always imported by `screen.scss` and `print.scss`
            - for further components, e.g. an `acc` component, there are two possiblities:
                1. `acc_screen.scss` and `acc_print.scss` are imported by `screen.scss` and `print.scss`, respectively. A potential `acc_common.scss` is imported by both `acc_screen.scss` and `acc_print.scss`
                2. `acc_all.scss` is imported by `screen.scss` and `print.scss`
3. Multiple templates may be composed
    - The underlying SCSS files are composed together and there is a convention of how this is done. The following example demonstrates this. Suppose that `sub-template` depends on `template`; both templates being self-sufficient.
    - The `sub-template` SCSS files (e.g. `screen.scss`) would then contain as first line e.g.
        ```
        @import "/modules-shared/template/scss/screen";
        ```