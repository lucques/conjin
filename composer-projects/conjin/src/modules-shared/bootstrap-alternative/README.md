# bootstrap-alternative

This internal module provides a small, standalone alternative to the Bootstrap features used by Conjin content. It implements the accordion and collapse macros exposed by `bootstrap-extended` without loading Bootstrap, together with compatible styles and browser behavior for a focused set of components.

The module defines the same global accordion, collapse, and variant functions as `bootstrap-extended`. The two modules must not be activated for the same processable.

## Features

- Accordion items and permanent blocks, including nesting, exclusive groups, header actions, variants, generated IDs, and print expansion.
- Automatically opens enclosing accordion panels for URL fragments, including nested accordions, on page load and hash changes.
- Standalone collapse controls with Bootstrap-compatible data attributes and events.
- Modal behavior with backdrops, focus management, keyboard and backdrop dismissal, and explicit-dismiss form resets.
- Target-specific primary-color customization through Bootstrap-style CSS properties.
- A focused subset of Bootstrap component and utility classes without the Bootstrap dependency.

## Usage

Activate the module for a target. The active template must support `doc-extensions`.

```php
$c->activate_module('bootstrap-alternative', [
    'primary' => '#6f42c1',
    'primary_rgb' => '111, 66, 193',
]);
```

Links such as `#details` to an element with `id="details"` inside an accordion automatically open all enclosing panels, from the outermost to the innermost, and scroll to the target. A fragment can also identify an `.accordion-collapse` panel itself. Exclusive groups close sibling panels as usual. This behavior is included in the automatically loaded `res/acc.js`; no project-specific `custom.js` or Bootstrap JavaScript API is needed.

## Configuration

- `primary`: A six-digit hexadecimal CSS color or `null`. The default is `null`, which preserves the built-in Bootstrap blue. Invalid values cause `InvalidArgumentException` during processing.
- `primary_rgb`: Three comma-separated integer channels from `0` to `255`, or `null`. The default is `null`. Invalid values cause `InvalidArgumentException` during processing.

Configure both values to representations of the same color. They are validated independently and emitted as `--bs-primary` and `--bs-primary-rgb` after the module stylesheets.

## API

### Accordion macros

`acc_start()` and `acc_end()` delimit an accordion. `acc_item_start()` and `acc_item_end()` delimit a collapsible item; `acc_block_start()` and `acc_block_end()` delimit a permanent block. The `acc_single_item_*()` and `acc_single_block_*()` pairs are shorthands. `acc_header_only()` renders a styled header and `acc_heading()` prints a heading-sized title. The start macros accept variants, custom colors, IDs, classes, and styles; items also support hidden and open states and header actions.

Pass `with_anchor: true` to `acc_item_start()`, `acc_block_start()`, `acc_single_item_start()`, or `acc_single_block_start()` to add a secondary-colored `#` permalink on the right of the title, before the collapse icon when present. The link targets the item or block, not the accordion set. Its optional `id` (or `item_id` / `block_id` for the single-item helpers) defaults to `kebabize($title)`; titles without usable slug characters fall back to the usual generated numeric ID. Supply distinct explicit IDs for repeated titles. Calls without `with_anchor` retain their existing ID behavior.

```php
acc_start();
acc_item_start('Details', with_anchor: true); // #details
// Collapsible content
acc_item_end();
acc_block_start('Summary', id: 'my-summary', with_anchor: true); // #my-summary
// Permanent content
acc_block_end();
acc_end();
```

The permalink is an `a.accordion-anchor` sibling of the collapse button, so hovering it does not hover the button and clicking it does not toggle an open panel closed. Following an item permalink opens that item and its enclosing panels, then scrolls to its title. This also works when clicking the current fragment again after manually closing the item. Blocks only require enclosing panels to open. Existing header actions remain separate controls.

### Collapse macros

`collapse_button()` prints a button and returns its generated collapse ID. `collapse_a_start()` and `collapse_a_end()` delimit a link-style trigger. Assign the returned ID to the corresponding `.collapse` element.

### `variant_to_color($variant)`

Returns the color class suffix for the standard theme colors, the additional `orange`, `green`, and `blue` colors, and the semantic variants shared with `bootstrap-extended`. Unsupported values fail an assertion.

## Implemented component classes

The module implements the Bootstrap-compatible component classes `.accordion`, `.collapse`, `.btn`, `.alert`, `.badge`, `.card`, `.form-control`, `.form-select`, `.form-check`, `.input-group`, `.modal`, and `.table`, including the variants and modifiers defined in the corresponding module stylesheets.

It also provides the small set of background, border, display, flex, float, gap, shadow, spacing, rounding, and text utilities needed by existing Conjin content. It is not intended to reproduce Bootstrap's complete class surface.

Text utilities include `.text-center`, `.text-nowrap`, and `.text-truncate`.

## Module relationships

### Dependencies

- `doc-extensions`: Activated automatically to add the module's stylesheets, scripts, and optional primary-color override to the document.

### Requirements

- The active template must render the elements collected by `doc-extensions`.

### Supported modules

- `html`: Styles `.html-heading-anchor` permalinks in the secondary color (`--bs-secondary`, falling back to `#6c757d`), including headings rendered by `nav_h_with_anchor()`.
- `sync-dims`: Opening collapse content triggers height synchronization when both modules are active.

## Structure and lifecycle

During preprocessing the module activates `doc-extensions`. Target processing adds the stylesheets and browser controllers, initializes macro state, and loads the accordion and collapse definitions. Syslet processing adds only the stylesheets. Accordion IDs are generated hierarchically; standalone collapse IDs are generated sequentially per target.

## Integration tests

The focused Playwright spec at `tests/integration/specs/e2e/expect-success/module-tests/bootstrap-alternative.spec.ts` verifies that `acc.js`, `collapse.js`, and `modal.js` are fetched without browser, console, or resource-loading errors and exercises their primary behavior. Run it from the repository root with `bin/test e2e-playwright /expect-success/module-tests/bootstrap-alternative.spec.ts`.

## Limitations and known problems

- The module implements only the documented compatibility subset, not Bootstrap as a whole.
- It cannot be activated together with `bootstrap-extended` because both modules define the same global PHP functions.
- `data-bs-target` supports fragment IDs rather than general CSS selectors or Bootstrap's JavaScript object API, and only one modal is intended to be open at a time.
- `primary` and `primary_rgb` are validated independently and not cross-checked for equality.
