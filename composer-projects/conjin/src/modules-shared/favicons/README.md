# favicons

Adds 32×32 shortcut-icon and 180×180 Apple touch-icon links to `<head>` for targets and syslets.

## Usage

```php
$c->activate_module('favicons', [
    'res_module' => 'my-assets',
    'path' => '/favicon',
]);
```

## Configuration

- `res_module`: Module containing the files; defaults to the active template.
- `path`: Directory below that module's `res`; defaults to `/favicon`.

The directory must contain `favicon-32x32.png` and `favicon-apple-touch-icon-180x180.png`.

## Module relationships

### Dependencies

- `doc-extensions`: Activated automatically to add the links to `<head>`.

### Requirements

- A template supporting `doc-extensions`.

## Examples

See the [demo page](../../../../demo-app/src/content/module-demos/favicons/index.php).
