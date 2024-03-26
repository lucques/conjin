# Technical Documentation of conjin

Documentation is spread out over multiple files.

- [Design decisions](./design_decisions.md): Problem description, motivation, advantages and disadvantages of chosen technologies
- [Deployment](./deployment.md)
    - [Deployment using Docker + Nginx](./deployment_docker_nginx.md): Deploy by running your own server
    - [Deployment using Docker + sync](./deployment_docker_sync.md): Deploy by syncing to an external server
- File structures:
    - [Conjin repo](./file_structure_conjin.md): File file structure of *this* repo
    - [Content repo](./file_structure_content.md): The file structure of the content repo
    - [`/htdocs` dir](./file_structure_htdocs.md): The file structure of the `htdocs` dir as established via Docker
- [Preprocessing](./preprocessing.md)
- [Processing](./processing.md)
- [Modules](./modules.md)
- Authentication + Authorization:
    - [Authentication](./auth_authentication.md): Login, logout etc.
    - [Authorization](./auth_authorization.md): Who can view what
- [Versioning](./versioning.md)
- [Changelog](./changelog.md)
- [Ideas](./ideas.md): Ideas for future development
- [Known bugs + limitations](./known_bugs_limitations.md)


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
        - The whole target tree gets traversed and folded
        - Should be used also for time-intensive tasks like server-side rendering etc.
        - Not needed when just changing some content of a target
    - Processing:
        - Triggered on every target request
- **Module**: See [Modules](./modules.md)
- **User**, **Group**, **Privilege**, **Action**: See [Authorization](./auth_authorization.md)
- **Path**: Internal file system, root points to `htdocs` folder path
- **URL**: Root points to `https://www.example.com/`


## Conventions
- **Mode**
    - A mode is a flag that can be set by the user when requesting a target, typically by adding the `?print=1` param to the URL.
    - Modes are always introduced by modules as they are not a built-in concept.
    - Examples: `print_mode` and `solution_mode`.
- **ID**
    - **Semantic id**:
        - Target ids, anchor ids, resource file names etc.
        - Lower case, separated by `-`
    - **Technical id**, like `id` and `class` attribute in DOM:
        - Separate by `_` when semantic ids are involved, to avoid clashing with `-`
- **Module package**
    - We do not declare all modules in every `index.php` file but use the
        - dependency mechanism
        - inheritance mechanism
    - ... to package the modules together.
    - Here is the conventions:
        - **Template module**: Modules of the name `template-<name>`
        - **Role module**: Modules of the name `role-<name>`
            - This is a module whose only purpose is to group together modules for a specific kind of page, called a role.
            - E.g., the `role-content` module groups together all modules that are needed to render an information/content page.
            - E.g., the `role-exercise` module groups together all modules that are needed to render an exercise page. 
        - We keep the modules which are strictly associated with a template as dependencies of that template.
            - E.g., the `nav` module is a dependency of the `template-book` module. This is obvious since `nav` is a dependency indeed.
            - E.g., the `mathjax` module is a dependency of the main template module. This is done since `mathjax` is used on most pages so we include it here.
        - This convention is not perfect (for future extension) but works for now, until it may change in the future. 