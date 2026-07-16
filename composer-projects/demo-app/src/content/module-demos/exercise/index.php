<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');
        $c->activate_module('exercise');
    };
?>

<? $process = function (Target $target) { ?>

<h1>exercise</h1>

<h2>Items, hints, and solutions</h2>

<? ex_start('Der Leuchtturm', open: true, only_one_open: true, class: 'demo-exercise', style: '--demo-exercise: 1;'); ?>
<p><strong>Bestimme</strong> die Höhe des Leuchtturms.</p>
<? ex_item('Information', variant: 'info'); ?>
<p>Die Entfernung und der Höhenwinkel sind bekannt.</p>
<? ex_hint('Tipp mit eigener Überschrift', open: true); ?>
<p>Zeichne zuerst ein rechtwinkliges Dreieck.</p>
<? ex_sol(open: true); ?>
<p>Berechne die Höhe mit dem Tangens.</p>
<? ex_end(); ?>

<p>Only one section of the exercise above remains open at a time. The accordion also demonstrates custom classes, inline styles, variants, and initially open sections.</p>

<h2>Numbering</h2>

<? ex_set_next_number(7); ?>
<? ex_start('Fortgesetzte Nummerierung'); ?>
<p>This exercise starts at number 7.</p>
<? ex_end(); ?>

<? ex_start(); ?>
<p>This exercise receives the next number and a generated title.</p>
<? ex_end(); ?>

<? ex_start(numbered: false); ?>
<p>This exercise receives the generic title <em>Aufgabe</em> without a number.</p>
<? ex_end(); ?>

<h2>Restricted and hidden solutions</h2>

<? ex_start('Sichtbarkeit von Lösungen'); ?>
<p>The first solution is visible to everyone.</p>
<? ex_sol(title: 'Öffentliche Lösung'); ?>
<p>This solution is unrestricted.</p>
<? ex_sol(restrict_to_static_users: ['root'], title: 'Lösung nur für root'); ?>
<p>This solution appears only when logged in as <code>root</code>.</p>
<? ex_sol(restrict_to_groups: ['teacher'], title: 'Lösung nur für teacher'); ?>
<p>This solution appears only to members of the <code>teacher</code> group.</p>
<? ex_sol(title: 'Immer verborgen', hidden: true); ?>
<p>This content is deliberately omitted.</p>
<? ex_end(); ?>

<? }; ?>
