<?
    $init_processing_target = function(Module $module, Target $target) {
?>

<? head_start(); ?>
<link rel="shortcut icon"    type="image/png" sizes="16x16"   href="<?= $target->get_template()->get_url() . '/res/favicon/favicon-16x16.png' ?>" />
<link rel="shortcut icon"    type="image/png" sizes="32x32"   href="<?= $target->get_template()->get_url() . '/res/favicon/favicon-32x32.png' ?>" />
<link rel="shortcut icon"    type="image/png" sizes="64x64"   href="<?= $target->get_template()->get_url() . '/res/favicon/favicon-64x64.png' ?>" />
<link rel="shortcut icon"    type="image/png" sizes="96x96"   href="<?= $target->get_template()->get_url() . '/res/favicon/favicon-96x96.png' ?>" />
<link rel="shortcut icon"    type="image/png" sizes="128x128" href="<?= $target->get_template()->get_url() . '/res/favicon/favicon-128x128.png' ?>" />
<link rel="shortcut icon"    type="image/png" sizes="256x256" href="<?= $target->get_template()->get_url() . '/res/favicon/favicon-256x256.png' ?>" />
<link rel="apple-touch-icon" type="image/png" sizes="152x152" href="<?= $target->get_template()->get_url() . '/res/favicon/favicon-apple-touch-icon-152x152.png' ?>" />
<link rel="apple-touch-icon" type="image/png" sizes="167x167" href="<?= $target->get_template()->get_url() . '/res/favicon/favicon-apple-touch-icon-167x167.png' ?>" />
<link rel="apple-touch-icon" type="image/png" sizes="180x180" href="<?= $target->get_template()->get_url() . '/res/favicon/favicon-apple-touch-icon-180x180.png' ?>" />
<? head_end(); ?>

<?
    };
?>