<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');

        $c->activate_module('localization');
        $c->run_macro('localization', 'set_language', 'de', true);
        $c->run_macro('localization', 'add_alternative', 'en', ['module-demos', 'localization', 'generic-en']);
    };
?>

<? $process = function (Target $target) { ?>

<p>
    Diese Seite ist auf Deutsch. Die entsprechende alternative Seite in Englisch ist <a href="../generic-en/">hier</a>.
</p>
<p>
    Folgende Sprachen sind verfügbar:
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
