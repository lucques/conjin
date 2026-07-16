# Klipse with Tau Prolog

The module provides Tau Prolog and a browser-based interpreter component.

## Usage

Activate the module while preprocessing:

```php
$c->activate_module('klipse-with-tau-prolog');
```

Add a mount element and initialize the interpreter:

```html
<div id="tau-prolog-interpreter"></div>

<script>
const interpreter = new tauPrologInterface.InterpreterComponent('tau-prolog-interpreter');
interpreter.init();
</script>
```
