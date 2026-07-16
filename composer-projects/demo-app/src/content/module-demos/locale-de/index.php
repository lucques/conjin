<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');
        $c->activate_module('locale-de');
    };
?>

<? $process = function (Target $target) { ?>

<h1>locale-de</h1>

<p>The module provides German-language helpers. Its weekday helper uses the same numbering as PHP's <code>date('w')</code>: Sunday is <code>0</code> and Saturday is <code>6</code>.</p>

<table class="table">
    <thead>
        <tr>
            <th scope="col">Weekday index</th>
            <th scope="col">German abbreviation</th>
        </tr>
    </thead>
    <tbody>
<?
    for ($weekday = 0; $weekday < 7; $weekday++) {
?>
        <tr>
            <td><code><?= $weekday ?></code></td>
            <td><?= weekday_abbrev($weekday) ?></td>
        </tr>
<?
    }
?>
    </tbody>
</table>

<? }; ?>
