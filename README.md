# Conjin

## Introduction
This is conjin, a PHP framework for rapidly publishing "programmable" web documents. 


## Ideas
- **Fast development cycle**: Publishing new content should be extremely fast (slightly over-simplified, but to give the idea):
    1. Create dir `./mypage`.
    2. Create file `./mypage/index.php` and write content.
    3. Now served on `http://host/mypage/`.
- **Programmable documents**: This is also the idea behind LaTeX, but PHP fits better for this job.
- **Dynamic, 1: Server-side rendering**: Documents should be dynamic. LaTeX produces a static document. But what if different people should see different versions of the document, e.g. student vs. teacher version?
- **Dynamic, 2: Web**: Should be available via web browser: Interactivity via JS.


## Design Decisions (short version)
Some design decisions are taken as follows. See more details and comments on disadvantages in [./doc/design_decisions.md](./doc/design_decisions.md).
- PHP
    - Easy deployment
    - Fast development cycle, no need to recompile, just reload the page 
    - Templating is built-in, everything is directed at producing output
    - Easy to write reusable macros
    - Well-established and large standard library
    - Good IDE support
- HTML & CSS
    - Complete control over page layout and styles
    - Good IDE support
- Docker
    - Easy deployment of orchestrated containers via docker-compose
- Dhall configuration language
    - Programmable configuration
    - Statically typed, makes it very hard to misconfigure
- LinkChecker
    - Find rendering errors quickly


## Architecture
If you decide to use conjin, the resulting app consists of three components:

1. **Conjin** (the `composer-projects/conjin/` Composer package): The Conjin framework provides the infrastructure, including the webserver entry point, libraries, macros, CSS styles, external runtime assets, and deployment tooling.
2. **Content** (part of your repo): This is a buch of easy-to-write PHP files, hierarchically stored in a filesystem. They automatically get included and served by the conjin app.
3. **Deployment** (part of your repo): This is essentially a single and small config file which ties 1. and 2. together. The built executable will orchestrate all necessary services via Docker.

This repository is a monorepo containing three sibling projects under `composer-projects/`: the reusable framework package, a human-facing demo app, and a deterministic integration-test fixture. Both applications depend only on the framework package. The repository root holds setup, documentation, tests, and supporting orchestration. A real application normally keeps its content and deployment configuration in its own repository and installs Conjin as a Composer dependency.


## Deployment
There are two predefined available deployment scenarios, both using Docker. See [./doc/deployment](./doc/deployment.md) for more details.


## Documentation
See [./doc/main.md](./doc/main.md).


## Version + Changelog
- **Versioning**: The current version is found in [composer-projects/conjin/composer.json](./composer-projects/conjin/composer.json). See more here: [doc/versioning.md](./doc/versioning.md).
- **Changelog**: The changelog is found here: [./doc/changelog.md](./doc/changelog.md).


## License
This project is licensed under GPLv3.
