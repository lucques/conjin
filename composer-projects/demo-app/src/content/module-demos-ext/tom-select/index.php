<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');

        $c->activate_module('title');
        $c->run_macro('title', 'set', 'Tom Select');

        $c->activate_module('tom-select');
    };
?>

<? $process = function (Target $target) { ?>

<label for="favorite-fruit">Favorite fruit</label>
<select id="favorite-fruit" class="tom-select" name="favorite-fruit[]" multiple placeholder="Choose up to three">
    <option value="apple">Apple</option>
    <option value="banana">Banana</option>
    <option value="cherry">Cherry</option>
    <option value="pear">Pear</option>
    <option value="strawberry">Strawberry</option>
</select>

<? }; ?>
