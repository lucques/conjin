# hamster

Provides source-listing and before/after helpers for Java Hamster Simulator material.

## Usage

```php
$c->activate_module('hamster');
```

Hamster source files must begin with the simulator header `/*imperative program*/`, which is omitted from the listing.

```php
hamster_source_file(__DIR__ . '/res/program.java', lang: 'java', line_numbers: true);
hamster_before_after('res/before.svg', 'res/after.svg');
```

## API

### `hamster_source_file(string $path, ?string $lang = null, ?bool $line_numbers = null, ?string $id = null, $class = '', $style = '')`

Prints an escaped source listing after removing the simulator header and forwards its display options to `source_listing()`.

### `hamster_source($path, $line_numbers = true, $class = '', $style = '')`

Prints the same source through the legacy `source_start()` and `source_end()` interface.

### `hamster_before_after($src_before, $src_after)`

Prints two responsive images side by side with “Vorher” and “Nachher” labels.

## Module relationships

### Dependencies

- `source`: Activated automatically to escape and format source listings.

### Requirements

- Compatible Bootstrap utility classes for the intended before/after layout.

## Examples

See the [demo page](../../../../demo-app/src/content/module-demos/hamster/index.php).

## Limitations and known problems

The source helpers always remove the first 22 bytes and therefore require the exact simulator header at the start of the file.
