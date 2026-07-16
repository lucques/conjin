<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-navigable', [
            'sidebar' => [
                'show_language_switcher_always' => true
            ]
        ]);
        $c->activate_module('nav-build');

        $c->activate_module('localization');
        $c->run_macro('localization', 'set_language', 'de', true);
    };
?>

<? $process = function (Target $target) { ?>
   
<p>
    Diese Seite gibt es nur auf Deutsch.
</p>
<p>
    Es soll immer die Sprachumschaltung angezeigt werden.
</p>

<? }; ?>
