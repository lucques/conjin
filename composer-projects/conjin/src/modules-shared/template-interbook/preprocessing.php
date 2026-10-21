<?
    $preprocess_target = function(Module $m, TargetPreprocessContext $c) {
        $sub_template = new ModuleLocation('template-navigable');
        $sub_template->load_def_from_script_and_call('preprocessing.php', 'preprocess_target', $m, $c);
    };

    $preprocess_syslet = function(Module $m, SysletPreprocessContext $c) {
        $sub_template = new ModuleLocation('template-navigable');
        $sub_template->load_def_from_script_and_call('preprocessing.php', 'preprocess_syslet', $m, $c);
    };
?>