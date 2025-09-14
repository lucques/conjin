# Agent Instructions

## Documentation

- Use `doc/` as the primary documentation directory for this repository.
- Read the relevant files in `doc/` when you need project context, architecture, build, deployment, or module documentation.

## Conjin Projects

- For projects based on the Conjin framework, when adding content such as pages, there is no need to run a full build. Run the preprocess command instead.
- Generally, do not run the build command. Leave this to the human.
- For SCSS files, there is no need to run any build command. The running docker setup cares for this automatically.
- When creating new files in a content directory, set permissions to match the other content files. Content files should be readable by all users.

## Shared Modules

- Shared modules live in:
  - `src/modules-shared/`
  - `ext/modules-shared/`
- When work touches a shared module, inspect that module directory for any local `AGENTS.md` or `agents.md` file and load it if present.
- Module-local agent instructions apply only when working in that specific module.
