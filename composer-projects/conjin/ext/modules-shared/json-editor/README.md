# json-editor

Optional JSON viewer using JSONEditor. Prints a searchable, collapsible, read-only tree for a JSON string or a server-side JSON file. Assets are loaded only when the module is activated; each call creates an independent viewer.

## Usage

Activate `json-editor` in the target or syslet preprocessing closure. Include `SharedBareModule.JsonEditor {=}` in a deployment that explicitly selects shared modules.

```php
<?
    $preprocess = function(TargetPreprocessContext $c) {
        $c->activate_module('json-editor');
    };

    $process = function(Target $target) {
        json_editor_print_inline('{"name":"Conjin","enabled":true}');
        json_editor_print_file(__DIR__ . '/example.json');
    };
?>
```

## API

### `json_editor_print_inline(string $json): void`

Prints a viewer container and queues initialization after DOM setup. Accepts any valid JSON value, including scalars and `null`. Invalid JSON throws `JsonException` before output is printed. Embedded strings are escaped for safe inclusion in HTML scripts. The container is full-width and 400 pixels high; override its inline height with `.conjin-json-editor { height: 600px !important; }` if needed.

### `json_editor_print_file(string $path): void`

Reads a local server-side file and delegates to `json_editor_print_inline`. Use an absolute path such as `__DIR__ . '/example.json'`; relative paths follow PHP's current working directory. Unreadable or missing files throw `RuntimeException`; invalid JSON throws `JsonException`. File contents are embedded in the page, so the file does not need a public URL. Pass trusted application paths only.

## Module relationships

### Dependencies

- `doc-extensions`: automatically activated to load assets and initialize viewers after DOM setup. The template must render this module's head elements and after-DOM scripts.

## Limitations and known problems

- The viewer does not edit or save files.
- JSON must be UTF-8 and at most 512 levels deep. Browser JavaScript number precision applies to numeric values; encode identifiers beyond the safe integer range as strings.

## Upstream project

- Project: [JSONEditor](https://github.com/josdejong/jsoneditor)
- Version: 10.4.3
- License: Apache-2.0; npm legal files are retained by the repository's asset setup.
- Source: pinned npm package `jsoneditor@10.4.3`, using `dist/jsoneditor.min.js`, `dist/jsoneditor.min.css`, and `dist/img/`.
- Local changes: none. The module build script copies the prebuilt distribution; it does not compile upstream sources.
