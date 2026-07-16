# localization

This module allows tagging a target with its language and providing links to other language versions of the same content. Template snippets enable language switching in the navigation.

## Usage

A target can serve as an **alternative** or a **nontranslation**:

- **alternative**: Contains actual translated content.
- **nontranslation**: Tells the user that translated content is unavailable.

Only alternatives are added to the document head. Alternatives and nontranslations appear in the language switcher.

### How to mark a target with a language

Use `true` if the page is an alternative and `false` if it is a nontranslation.

```php
$c->run_macro('localization', 'set_language', 'de', true);
```

### How to add another language version of the same content

```php
$c->run_macro('localization', 'add_alternative', 'de', ['first', 'second']);
```

### How to add a nontranslation

```php
$c->run_macro('localization', 'add_nontranslation', 'de', ['first', 'second']);
```

## Examples

See the [demo pages](../../../../demo-app/src/content/module-demos/localization/index.php).
