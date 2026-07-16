# mathjax-extensions

This module mainly includes MathJax and adds some default configuration for it.

It also exposes PHP helpers for aligned equation sets:

```php
mjax_eqset_start();
mjax_eqset_line($left, $right, $transformation = null, $show_bar = true, $sign = '=', $margin_bottom_cm = null);
mjax_eqset_end();
```

If `margin_bottom_cm` is set, the helper emits `\\[<margin_bottom_cm>cm]`, which adds extra vertical space after that row.

When an equivalence transformation uses `\pm \sqrt{...}`, the result is two equations. End the active equation set with `mjax_eqset_end()` at that point and continue the two cases below, using separate equation sets if the cases need further transformations.


## Hooks
If you want to trigger some function call as soon as MathJax is done rendering the document, you can install a hook as follows. It does not matter where in the DOM to put this code: Whenever MathJax is done rendering (this will happen very late, after the `DOMContentLoaded` event), it will pick up this global variable. The following is an example of installing the `setupPagedJs` function as a hook.

```html
<script>
if (typeof window.mathJaxAfterRenderingHooks !== 'undefined') {
    window.mathJaxAfterRenderingHooks.push(setupPagedJs);
}
else {
    window.mathJaxAfterRenderingHooks = [setupPagedJs];
}
</script>
```


## `tightarray` environment
This is the math environment I was long looking for.

Usage:
```
\begin{tightarray}[<spacing>]{<alignment>}
  row1col1 & row1col2 & row1col3 \\
  row2col1 & row2col2 & row2col3
\end{tightarray}
```
where
- `<spacing>`: Optional. Specifies the space between columns. Defaults to 0em if not provided.
- `<alignment>`: Required. Specifies how each column should be aligned. Use r for right, l for left, and c for center alignment. Each letter in the alignment string corresponds to a column.

You can also use it together with an `alignedat` environment.
```
\begin{tightarray}{ll}
&~~~~~~~~~ 3+ 2 \cdot 42 + 2^{5}\\
&~=~ 3\\
&
  \begin{alignedat}[t]{3}
    &~=~(3\,+\,&(2 \cdot 42)) &+ (2^{5})\\
    &~=~(3\,+\,&84~~~~)           &+ ~32\\
    &~=~&87~~~~~~~~~          &+~32\\
    &~=~&119&             
  \end{alignedat}\\
\end{tightarray}
```

## Examples

See the [demo page](../../../../demo-app/src/content/module-demos/mathjax-extensions/index.php).
