# java-worlds

Provides `JavaWorldsPrinter`, a processing helper for including Java source listings, project downloads, UML files, and other static files from a java-worlds repository stored below the application's collected content directory. The module does not render a standalone component; pages create a printer for a repository and call the required output or path helpers.

## Features

- Prints escaped Java source files through the `source` module.
- Prints links to downloadable project archives.
- Resolves URLs for variant-level UML files and program-level UML or static files.
- Encapsulates the expected java-worlds repository layout.


## Module relationships

### Dependencies

- `bootstrap`
- `source`

### Requirements

- The configured repository must be stored below the application's collected content directory for URL conversion to work.
- Variant, program, and file names must be trusted path components. The module does not validate them or prevent path traversal.