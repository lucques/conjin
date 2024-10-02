<?
    $init_processing_target = function(Module $module, Target $target) {
        $res_module = $module->config->get('res_module') === null
            ? $target->get_template()
            : new ModuleLocation($module->config->get('res_module'));
        $url_prefix = $res_module->get_url() . '/res/favicon/favicon-';
?>

<? head_start(); ?>
<link rel="shortcut icon"    type="image/png" sizes="16x16"   href="<?= $url_prefix . '16x16.png' ?>" />
<link rel="shortcut icon"    type="image/png" sizes="32x32"   href="<?= $url_prefix . '32x32.png' ?>" />
<link rel="shortcut icon"    type="image/png" sizes="64x64"   href="<?= $url_prefix . '64x64.png' ?>" />
<link rel="shortcut icon"    type="image/png" sizes="96x96"   href="<?= $url_prefix . '96x96.png' ?>" />
<link rel="shortcut icon"    type="image/png" sizes="128x128" href="<?= $url_prefix . '128x128.png' ?>" />
<link rel="shortcut icon"    type="image/png" sizes="256x256" href="<?= $url_prefix . '256x256.png' ?>" />
<link rel="apple-touch-icon" type="image/png" sizes="152x152" href="<?= $url_prefix . 'apple-touch-icon-152x152.png' ?>" />
<link rel="apple-touch-icon" type="image/png" sizes="167x167" href="<?= $url_prefix . 'apple-touch-icon-167x167.png' ?>" />
<link rel="apple-touch-icon" type="image/png" sizes="180x180" href="<?= $url_prefix . 'apple-touch-icon-180x180.png' ?>" />
<? head_end(); ?>

<?
    };
?>