# js-standard-lib

Provides small, browser-global JavaScript helpers.

## Usage

Activate `js-standard-lib`, then call its functions from browser scripts.

## API

### `assert(condition, message = 'No message')`

Logs failed assertions without throwing.

### `nextUniqueId()`

Returns a page-unique sequential ID.

### `getURLParameter(param)`

Returns a query parameter or `null`.

### `updateURLParameter(param, value)`

Adds, updates, or removes a query parameter and pushes the URL to browser history.

See the [demo page](../../../../demo-app/src/content/module-demos/js-standard-lib/index.php).

## Module relationships

### Dependencies

- `doc-extensions`: Loads the browser script.
