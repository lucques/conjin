# Technical Documentation of conjin

Documentation is spread out over multiple files.

- [Design decisions](./design_decisions.md): Problem description, motivation, advantages, disadvantages of chosen technologies
- [Dependencies](./dependencies.md)
- [Deployment](./deployment.md)
    - [Deployment using Docker + Nginx](./deployment_docker_nginx.md): Deploy by running your own server
    - [Deployment using Docker + sync](./deployment_docker_sync.md): Deploy by syncing to an external server
- File structures:
    - [Conjin repo](./file_structure_conjin.md): File file structure of *this* repo
    - [Content repo](./file_structure_content.md): The file structure of the content repo
    - [`/htdocs` dir](./file_structure_htdocs.md): The file structure of the `htdocs` dir as established via Docker
- Preprocessing + Processing:
    - [Preprocess](./preprocess.md)
    - [Process](./process.md)
- [Modules](./modules.md)
- Authentication + Authorization:
    - [Authentication](./auth_authentication.md): Login, logout etc.
    - [Authorization](./auth_authorization.md): Who can view what
- [Versioning](./versioning.md)
- [Changelog](./changelog.md)
- [Ideas](./ideas.md): Ideas for future development
- [Known bugs + limitations](./known_bugs_limitations.md)


## Definitions
- **Processable**: Either a target or a syslet
    - Contains a list of activated modules, each with a configuration
    - Is the result of a preprocessing step
    - **Target**
        - Targets are organized in a tree structure, where each node...
            - is represented by a `Target` obj 
            - has a target id (only a list of target id's therefore specifies the path to a specific target)
        - Usually contains content, three types:
            - **Inline**: In `index.php`, there is a `process` function
            - **Extra**: In `content.php`, the content is hardcoded (no function)
            - **None**: No content. The node is just a container and cannot be requested by itself 
    - **Syslet**
        - External view: A webpage that fulfills a pre-defined system function such as "login", "not found" etc.
        - Internal view: A file `login.php`, `not_found.php` etc. within the `system` dir
- **Preprocessing phase**
    - Main idea: Collect all "meta information" (target title, config etc.) to build a navigation etc.
    - Gets triggered explicitly, which results in a target tree and the syslets
    - Should be used also for time-intensive tasks like server-side rendering etc.
    - Not needed when just changing some content of a target
- **Processing phase**
    - Triggered by the typical HTTP request
    - Inits modules, template and renders the target / syslet.
- **Module**: See [Modules](./modules.md)
- **User**, **Group**, **Actor**, **Privilege**, **Target Action**, **Custom Target Action**: See [Authorization](./auth_authorization.md)
- **Path**: Internal file system; root points to `htdocs` folder path
- **URL**: Root points to `https://www.example.com/`


## Conventions
- **ID**
    - **Semantic id**:
        - Target ids, anchor ids, resource file names etc.
        - Lower case, separate parts by `-` (kebab case)
    - **Technical id**, like `id` and `class` attribute in DOM:
        - Separate parts by `_` when semantic ids are involved, to avoid clashing with `-`
        - This may result in a weird combination like `first-order_second-order`, but that's fine
- **Mode**
    - A mode is a flag that can be set by the user when requesting a target, typically by adding the `?print=1` param to the URL.
    - Modes are always introduced by modules as they are not a built-in concept.
    - Examples: `print-mode` and `sol-mode`.
- **Module**: See [Modules](./modules.md)