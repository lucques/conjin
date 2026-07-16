# dynamic-toggle

Toggles CSS classes on `<body>` in the browser and persists each state under `dynamic-toggle-<css_class>` in local storage.

## Usage

```php
$c->activate_module('dynamic-toggle', [
    'toggles' => [
        [
            'css_class' => 'sidebar-active',
            'on_by_default' => true,
        ],
    ],
]);
```

```html
<button type="button" onclick="dtToggle('sidebar-active')">Toggle sidebar</button>
```

## Configuration

- `toggles`: List of toggles; defaults to `[]`.
- `toggles[].css_class`: Body class and storage-key suffix.
- `toggles[].on_by_default`: Boolean used when no state has been saved.

## API

### `dtToggle(cssClass)`

Toggles and persists the state.

### `dtActivate(cssClass, modeOn)`

Sets and persists the state.

### `dtIsOn(cssClass)`

Returns whether `<body>` has the class.

### `dtSetup(cssClass)`

Persists and reapplies the body's current state; called automatically during page setup.

## Module relationships

### Dependencies

- `doc-extensions`: Activated automatically to load and initialize the browser code.

### Requirements

- A template supporting `doc-extensions`.
- A browser with local-storage support.