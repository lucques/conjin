# grading

Provides grading configurations, point ranges, and printable grade lists for Gymnasien in Mecklenburg-Vorpommern. The configurations can be replaced with compatible custom arrays.

## Terminology

- **Raw/fine (Klassen 7–10)**: Raw grades omit tendencies; fine grades include them. The tendency interval around raw grade 2 at 80%, for example, yields 2 from 83%, 2− from 80%, and 3+ from 77%.
- **Test/exam (Klassen 11–12)**: Tests and exams use different 15-point scales.
- **Smallest point unit**: A value of 0.5 means thresholds and ranges use half points.

## Usage

Activate the module for a target and use one of its predefined configurations:

```php
$c->activate_module('grading');
```

```php
$ranges = grading_table_points(GRADING_SEK_1_RAW, 100);

$list = new GradeList(GRADING_SEK_1_RAW, [8, 12]);
$list->add('Lovelace', 'Ada', [7, 10]);
$list->print();
```

## API

### Grading configurations

- `GRADING_SEK_1_RAW`: Grades 1–6 without tendencies.
- `GRADING_SEK_1_FINE`: Grades 1–6 with tendencies.
- `GRADING_SEK_2_TEST`: Points 15–0 for tests.
- `GRADING_SEK_2_EXAM`: Points 15–0 for exams.

Each configuration contains relative minimums, the denominator of the smallest point unit, and the rounding precision applied before rounding upward to an attainable score.

### `grading_table_points(array $grading_config, int $max): array`

Returns each grade's inclusive minimum and maximum score for the given total points.

### `GradeList`

`__construct(array $grading_config, array $max_points, int $converted_to_bonus_points = 0)` creates a list. `add(string $student_last, string $student_first, ?array $points = null, ?float $bonus_points = 0)` adds a populated or blank row, and `print()` renders the table.

### `GradeListEntry`

`get_total_points()` returns exercise and bonus points; `get_grade(array $grading_config, int $max_points)` returns the matching grade. Both require assigned exercise points.

## Module relationships

### Requirements

- Compatible Bootstrap table styles for the intended `GradeList` presentation.

## Examples

See the [demo page](../../../../demo-app/src/content/module-demos/grading/index.php).

## Limitations and known problems

### Rounding for `GRADING_SEK_2_TEST`

The table in Anlage 2 of the 2019 APVO M-V cannot be reproduced with a consistent rounding rule. The most consistent rule is to round the calculated threshold to two decimal places before rounding upward to the next half point. Even then, the official table differs in two cells: with 56 total points, grade 14 starts at 55 instead of the consistently calculated 54.5 points; with 92 total points, grade 11 starts at 79 instead of 78.5 points. The implementation applies the two-decimal rule consistently, so students receive the better grade in both cases.
