# exercise

Provides numbered, multi-section exercises as accordions. Exercise sets receive the `exercise` class, and solutions can be restricted to authorized groups or static users.

## Usage

```php
$c->activate_module('exercise');
```

```php
<? ex_start('Title'); ?>
Exercise
<? ex_hint(); ?>
Hint
<? ex_sol(); ?>
Solution
<? ex_end(); ?>
```

## API

### `ex_start($title = null, bool $numbered = true, $variant = 'exercise', $open = false, $only_one_open = false, $class = '', $style = '')`

Starts an exercise and its first accordion item. Numbering starts at 1; presentation arguments are passed to the accordion.

### `ex_item($title, $variant = null, $open = false)`

Ends the current item and starts another exercise section.

### `ex_hint($title = '<strong>Tipp</strong>', $variant = 'hint', $open = false)`

Starts a hint section.

### `ex_sol($restrict_to_groups = [], $restrict_to_static_users = [], $title = 'Lösungsvorschlag', $variant = 'solution', $open = false, $hidden = false)`

Starts a solution section. It is omitted when explicitly hidden or when the current user matches neither allowed list; empty lists allow everyone.

### `ex_end()`

Ends the current item and exercise. Every `ex_start()` must be paired with `ex_end()`.

### `ex_set_next_number(int $number)`

Sets the number used by the next numbered exercise.

## Module relationships

### Dependencies

- `bootstrap-extended`: Activated automatically to provide accordions.

## `sol-mode` versus `exercise`

Use `sol-mode` when the whole page is one exercise with a shared solution mode. Use `exercise` for multiple exercises with individual solutions.

## Examples

See the [demo page](../../../../demo-app/src/content/module-demos/exercise/index.php).

## Limitations and known problems

An exercise missing its final `ex_end()` is not currently detected. TODO: Add an end-of-processing consistency check.
