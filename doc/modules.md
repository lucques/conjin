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
- A module is activated in **target**s and **syslet**s.
- Three possible functions that a module can take up, any combination possible:
    - **Preprocessing**
        A module can hook into the preprocessing mechanism by implementing the `$preprocess_target` and `$preprocess_syslet` functions. These functions then can:
        - Activate and configure other modules
        - Create and add a preprocessor object to the preprocessing context. This is used e.g. by `nav-build` to build up the navigation tree.
        - Provide preprocessing macros to be used by other modules during the preprocessing phase.
    - **Processing**
        - A module can hook into the processing mechanism by implementing the `$process_target` and `$process_syslet` functions. These functions get access to the currently-processed target / syslet obj. This allows the definition of macros etc. that can then be used later by the `index.php` content files, other modules and template files.
    - **Templating**
        - A module may serve as a template. There are e.g. `target.php`, `login.php` that both provide a function that renders output for processables, while given access to the currently-processed processable (see more below).


## Files and Functions:
- `default_config.php`
    - `default_config: () -> dict<string, mixed>`
    - Returns the default config of the module; must be complete
- `preprocessing.php`
    - Loaded on module activation, or when a preprocessing macro is requested. Top-level definitions are loaded once per PHP execution; entrypoints run separately for each preprocessing context.
    - Optional entrypoint defs:
        - `preprocess_target: (Module, TargetPreprocessContext) -> ()` only called for targets
        - `preprocess_syslet: (Module, SysletPreprocessContext) -> ()` only called for syslets, including login profiles
        - May declare module dependencies by calling `$c->activate_module` etc. Results in a DAG of activated modules.
- `processing.php`
    - Loaded for each activated module before rendering content or a template. Top-level PHP functions can define processing macros even when neither entrypoint is present.
    - Optional entrypoint defs:
        - `process_target: (Module, Target $target) -> ()` only called for targets
        - `process_syslet: (Module, Syslet $syslet, Target $target_root) -> ()` only called for syslets, including login profiles
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
- `scss/`:
    - Contains SCSS files that are fed into the SCSS compiler
- `res/`:
    - Contains resources needed by the module


## Entrypoints and local code sharing

The framework calls only the entrypoint matching the current target or syslet. There is no automatically executed common hook. Both entrypoints are optional, and module files may be absent. The filenames remain `preprocessing.php` and `processing.php`; the `$preprocess` and `$process` closures in content and system files are unchanged.

When both entrypoints do exactly the same work, they can reference one local closure using the common argument type:

```php
<?
    $preprocess_target = $preprocess_syslet = function(Module $m, PreprocessContext $c) {
        $c->activate_module('doc-extensions');
    };
```

The equivalent processing pattern uses `$process_target = $process_syslet = function(Module $m, Processable $p) { ... };`. A shared closure can omit the additional root-target argument supplied for syslets when it does not need it.

When the work differs, share a local helper and call it explicitly wherever needed:

```php
<?
    $activate_common = function(Module $m, PreprocessContext $c) {
        $c->activate_module('title');
    };

    $preprocess_target = function(Module $m, TargetPreprocessContext $c) use ($activate_common) {
        $activate_common($m, $c);
        $c->activate_module('nav-build');
    };

    $preprocess_syslet = function(Module $m, SysletPreprocessContext $c) use ($activate_common) {
        $activate_common($m, $c);
    };
```

The module owns the placement of each helper call. Helpers are not automatically invoked by the framework. Derived templates forwarding to another module must call its matching `preprocess_target` or `preprocess_syslet` definition and pass the derived module configuration.

## Dependencies
- Declaration via calling `activate_module` during `$preprocess_target` or `$preprocess_syslet`
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


### Conventions for Templates
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


## README.md files

Each module must contain a `README.md`. Existing READMEs generally combine a short purpose statement with some of the following: features, terminology, usage, configuration, API, structure, integration with other modules, and known limitations. External modules additionally record the upstream project, bundled version, source, license, and relevant download or build choices.

Use the following order where applicable. The title and introductory description are required; the other sections are optional and should be omitted when they have nothing useful to say. Public configuration and APIs should not remain undocumented merely because a module is small.

# module-name

Describe what the module provides, when it is useful, and its main scope in one or two paragraphs. Mention important lifecycle or persistence behavior here when it is fundamental to understanding the module.

## Features

- Summarize the important capabilities and deliberate boundaries of the module.
- Prefer user-visible behavior over implementation details.

## Terminology

- **Term**: Define module-specific vocabulary needed by the rest of the README.

## Usage

Explain how to activate and use the module. Include a minimal representative example and identify whether it is intended for targets, syslets, templates, or other modules.

## Configuration

- `option_name`: Describe its type, default value, effect, and important interactions.

## API

### `public_function_or_method(arguments)`

Describe its inputs, result, side effects, lifecycle constraints, and failure conditions. Cover all public preprocessing macros, processing functions and classes, template entry points, and JavaScript APIs exposed by the module.

## Module relationships

### Dependencies

- `dependency-name`: Explain why this module requires it and whether it is activated automatically. Include direct PHP/preprocessing dependencies as well as required SCSS or asset dependencies, and distinguish those mechanisms when relevant.

### Requirements

- Describe requirements that are not declared module dependencies, such as the presence of a compatible Bootstrap stylesheet, a writable store, a database, or a browser API.

### Supported modules

- `supported-module-name`: Explain the integration provided when both modules are active. Supporting another module does not imply activating or depending on it.

### How to support this module

Describe the contract another module or template must implement to support this module, such as rendering collected footnotes, inserting document extensions, providing a toggle button, or supplying required CSS and DOM structure.

### Used or extended by

- `consumer-name`: Mention important modules known to depend on, wrap, or extend this module when that reverse relationship helps users choose the correct abstraction. Do not attempt to list every incidental consumer.

## Structure and lifecycle

Describe important file, SCSS, DOM, initialization, state, persistence, or rendering structure that users or integrators must preserve.

## Examples

Provide larger or alternative examples when the minimal example under Usage does not cover the important workflows. Link to the corresponding demo-app demo where one exists.

## Limitations and known problems

- Document unsupported cases, browser-specific behavior, compatibility constraints, and known bugs.

## Upstream project

This section is required for external modules and normally unnecessary for internal modules.

- Project: upstream project name and canonical URL
- Version: bundled version
- License: upstream license
- Source: download URL, selected distribution or build, and download date where useful
- Local changes: patches, configuration, selected plugins, omitted assets, or other differences from the upstream distribution

The relationship headings have distinct meanings:

- **A depends on B** means A requires B and normally activates it through `$c->activate_module()`. The activated modules form a dependency DAG. SCSS and asset dependencies must also be identified even when their Docker/Dhall declaration lives outside PHP.
- **A requires B** records a prerequisite that is not expressed as module activation. For example, an icon wrapper may require compatible CSS to be present without choosing or activating a particular CSS module.
- **A supports B** means A implements B's integration contract when both are active; it does not imply that A activates B. The README for A lists B under `Supported modules`, while the README for B explains the contract under `How to support this module`.
- **A is used or extended by B** is the reverse view of a dependency or extension relationship. Record it only for important, stable consumers; the source remains authoritative for the complete dependency graph.


## Public resources and private implementations

Deployed module directories, including generated CSS directories, live in private storage. Module filesystem paths returned by `ModuleLocation::get_path()` continue to work. Public URLs returned by `get_url()` and `get_css_url()` also stay unchanged, but only their public resources are served, through the public `module-resource.php` bootstrap and its private framework implementation. Source modules expose `res/` files. Compiled directories also expose `.css` and `.css.map` files directly at their root or in compiler-generated subdirectories, preserving URLs such as `/modules-shared-css/template-interbook/print.css`. The handler does not initialize authentication or require preprocessed state because module resources are public.

Keep all browser assets inside `res/`. Templates, preprocessing and processing scripts, SCSS, and default configuration files are implementation details and are not directly accessible over HTTP. Resource requests cannot escape their resource directory using traversal or symlinks. Dotfiles and PHP-related files (`php`, numbered PHP extensions, `phtml`, `phar`, and `inc`) are not served, including mixed-case extensions and symlinks to such files. Do not place secrets in `res/`: its other files are intentionally public.

The handler supports GET, HEAD, Last-Modified conditional requests, and explicit MIME types for JavaScript, CSS, WebAssembly, JSON, SVG, and web fonts. Content resources continue through Conjin's target authorization and now use the same resource-directory containment checks.

## Resource processing

A module may implement `$process_resource = function(Module $module, Target $target, string $resource_path, string $resource_dir): void`. Conjin calls it on the resource owner's activated modules after authorization and successful resolution, before conditional-cache handling. `$resource_path` is the resolved file path relative to the resource directory; `$resource_dir` is `res` for ordinary resources or `inc/<include-path>res` for included resources. Callers interested only in ordinary resources must check this directory explicitly. Missing and unauthorized resources do not invoke this callback. The hook precedes final status determination; defer status-sensitive work until shutdown. Output is discarded, and callback exceptions and warnings are isolated so optional observers do not block file delivery. The module's processing definitions load even when it has no resource callback; top-level processing code should therefore define helpers and callbacks without request-specific side effects.
