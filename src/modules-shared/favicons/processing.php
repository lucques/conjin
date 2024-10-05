<?
    $init_processing_target = function(Module $module, Target $target) {
        $res_module = $module->config->get('res_module') === null
            ? $target->get_template()
            : new ModuleLocation($module->config->get('res_module'));

        $module->load_def_from_script_and_call('processing.php', 'render', $res_module, $module->config->get('path'));
    };

    $init_processing_syslet = function(Module $module, Syslet $syslet) {
        $res_module = $module->config->get('res_module') === null
            ? $syslet->get_template()
            : new ModuleLocation($module->config->get('res_module'));

        $module->load_def_from_script_and_call('processing.php', 'render', $res_module, $module->config->get('path'));
    };

    $render = function(ModuleLocation $res_module, string $path) {
        $url_prefix = $res_module->get_url() . '/res' . $path . '/favicon-';
?>
<? head_start(); ?>
<link rel="shortcut icon"    type="image/png" sizes="32x32"   href="<?= $url_prefix . '32x32.png' ?>" />
<link rel="apple-touch-icon" type="image/png" sizes="180x180" href="<?= $url_prefix . 'apple-touch-icon-180x180.png' ?>" />
<? head_end(); ?>

<?
    };
?>