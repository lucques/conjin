# Conjin

## Introduction
This is conjin, a PHP framework for publishing content. If you decide to use conijn, then the resulting app consists of three components (= repos):

1. **Conjin** (this repo): The conjin framework provides the infrastracture, i.e. the entrypoint to the webserver, libraries, macros, CSS styles and more.
2. **Content** (part of your repo): This is a buch of easy-to-write PHP files, hierarchically stored in a filesystem. They automatically get included and served by the conjin app.
3. **Deployment** (part of your repo): This is essentially a single and small config file which ties 1. and 2. together. The built executable will orchestrate all necessary services via Docker.


## Ideas
- **Fast development cycle**: Publishing new content should be extremely fast: Create new dir. Create `mypage/index.php`. Run `http://host/mypage/`. Done.
- **Programmable documents**: This was also the idea behind LaTeX, but PHP fits better for this job.
- **Dynamic, 1: Server-side rendering**: Documents should be dynamic. LaTeX produces a static document. But what if different people should see different versions of the document, e.g. student vs. teacher version?
- **Dynamic, 2: Web**: Should be available via web browser: Interactivity via JS.


## Design Decisions (short version)
Some design decisions are taken as follows. See more details and comments on disadvantages in [./doc/main.md](./doc/main.md).
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
    - Find rendering errors very quickly


## Deployment
There are two predefined available deployment scenarios, both using Docker. See [./doc/main.md](./doc/main.md) for more details.


## License
This project is licensed under GPLv3.