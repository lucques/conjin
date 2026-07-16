<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');

        $c->activate_module('title');    
        $c->run_macro('title', 'set', 'source');
        
        $c->activate_module('source', [
            // 'language' => 'java',
            'line_numbers' => false
        ]);
    };
?>

<? $process = function (Target $target) { ?>

<? html_h(2, 'Inline im Text'); ?>
<p>
    Hier kommt etwas inline <code>SELECT * FROM lalala</code> und hier wieder normaler Text. Hier noch etwas HTML: <? source_in('<strong>stark</strong>', lang: 'html'); ?>
</p>

<? html_h(2, 'source_scope'); ?>
<p>
    Change now the language to java.
</p>
<? source_scope_start(lang: 'java'); ?>
<p>
    Hier etwas Java: <code>public static void meineMethode</code> und das war's auch schon.
</p>
<? source_scope_end(); ?>

<p>
    Change now the language to css.
</p>
<? source_scope_start(lang: 'css'); ?>
<p>
    Hier etwas CSS: <code>p {color:red;}</code> und das wars.
</p>
<? source_scope_end(); ?>


<? html_h(2, 'source_start und source_end'); ?>

Java:
<? source_start(lang: 'java'); ?>
SELECT * FROM boote
WHERE laenge > 100
<? source_end(); ?>

HTML
<? source_start(lang: 'html', line_numbers: true); ?>
<h1>Ein Titel</h1>
<p>Ein Absatz</p>
<? source_end(); ?>

<? html_h(2, 'source_file'); ?>

HelloWorld.java
<? source_file(__DIR__ . '/res/HelloWorld.java', lang: 'java', line_numbers: true); ?>

index.html
<? source_file(__DIR__ . '/res/index.html', lang: 'html', line_numbers: true); ?>


<? html_h(2, 'source_file_block'); ?>

Akzeptor.java: Funktion <code>testWortFuerL3</code>
<? source_file_block(__DIR__ . '/res/Akzeptor.java', 'static boolean testWortFuerL3(String wort)', '}', lang: 'java'); ?>
index.html: Dritter Absatz
<? source_file_block(__DIR__ . '/res/index.html', '<p>', '</p>', skip_block_openings: 2, lang: 'html'); ?>

<head>

<? }; ?>