<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');
        $c->activate_module('html');
    };
?>

<? $process = function (Target $target) { ?>

<h1>html</h1>

<h2>Heading</h2>

<? html_h(3, 'Generated heading', id: 'generated-heading', class: 'text-primary', style: 'letter-spacing: 0.03em;'); ?>

<h2>Image</h2>

<? html_img('res/example.svg', alt: 'Abstract example graphic', width: 240, height: 120, id: 'generated-image', class: 'img-fluid', style: 'border-radius: 0.5rem;'); ?>

<h2>Truncated paragraph</h2>

<? html_p_truncated('This text is immediately visible.', 'This additional paragraph can be expanded and collapsed.'); ?>

<h2>Email link</h2>

<p><? html_a_mailto('demo@example.org'); ?></p>

<? }; ?>
