<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');

        $c->activate_module('localization');
        $c->run_macro('localization', 'set_language', 'en', true);
        $c->run_macro('localization', 'add_alternative', 'de', ['module-demos', 'localization', 'generic-de']);
    };
?>

<? $process = function (Target $target) { ?>

<p>
    This page is in English. The corresponding alternative page in German is <a href="../generic-de/">here</a>.
</p>
<p>
    The following languages are available:
</p>
<ul>
<?
    $alternatives = get_language_alternatives_and_nontranslations(['de', 'en']);
    foreach ($alternatives as $lang => $target_ids) {
?>
    <li><a href="<?= url_collect($target_ids) ?>"><?= language_to_flag($lang) ?> <?= language_to_text($lang) ?></a></li>
<?
    }
?>
</ul>

<? }; ?>
