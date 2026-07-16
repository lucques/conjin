# footnotes

Collects inline footnotes for a supporting template to render at the end of a target.

## Usage

```php
$c->activate_module('footnotes');
```

```php
Text<? footnote_start(); ?>Footnote<? footnote_end(); ?>
```

## API

### `footnote_start()`

Starts capturing a footnote.

### `footnote_end()`

Stores the captured content and prints its numbered reference and link.

### `footnotes_any(): bool`

Returns whether footnotes were collected.

### `footnotes_print_list()`

Prints the numbered notes and backlinks, or nothing when the list is empty.

## Module relationships

### How to support this module

A template must call `footnotes_print_list()` after the target content, normally guarded by `footnotes_any()`.

## Examples

See the [demo page](../../../../demo-app/src/content/module-demos/footnotes/index.php).
