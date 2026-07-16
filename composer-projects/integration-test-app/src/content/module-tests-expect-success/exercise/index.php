<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');
        $c->activate_module('exercise');
    };
?>

<? $process = function (Target $target) { ?>

<? ex_start('First exercise', open: true, only_one_open: true, class: 'exercise-contract', style: '--exercise-marker: 1;'); ?>
<p id="exercise-main-content">Main exercise content</p>
<? ex_item('Information', variant: 'info'); ?>
<p id="exercise-information">Information content</p>
<? ex_hint('Custom hint', open: true); ?>
<p id="exercise-hint">Hint content</p>
<? ex_sol(title: 'Public solution', open: true); ?>
<p id="exercise-public-solution">Public solution content</p>
<? ex_sol(restrict_to_groups: ['teachers'], title: 'Group-restricted solution'); ?>
<p id="exercise-group-restricted-solution">Group-restricted solution content</p>
<? ex_sol(restrict_to_static_users: ['root'], title: 'User-restricted solution'); ?>
<p id="exercise-user-restricted-solution">User-restricted solution content</p>
<? ex_sol(title: 'Explicitly hidden solution', hidden: true); ?>
<p id="exercise-hidden-solution">Explicitly hidden solution content</p>
<? ex_end(); ?>

<? ex_set_next_number(7); ?>
<? ex_start('Renumbered exercise'); ?>
<p>Renumbered content</p>
<? ex_end(); ?>

<? ex_start('Unnumbered exercise', numbered: false); ?>
<p>Unnumbered content</p>
<? ex_end(); ?>

<? }; ?>
