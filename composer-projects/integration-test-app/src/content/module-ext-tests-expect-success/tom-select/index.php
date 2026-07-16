<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');
        $c->activate_module('tom-select');
    };
?>

<? $process = function (Target $target) { ?>

<label for="tom-select-fruit">Fruit</label>
<select id="tom-select-fruit" class="tom-select" name="fruit[]" multiple>
    <option value="apple">Apple</option>
    <option value="banana">Banana</option>
    <option value="cherry">Cherry</option>
    <option value="pear">Pear</option>
</select>

<? }; ?>
