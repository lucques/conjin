<?
    $init_preprocessing = function(Module $m, PreprocessContext $c) {
        
        /////////////////////////////////
        // Taken from the sub template //
        /////////////////////////////////

        $sub_template = new ModuleLocation('template-navigable');
        $sub_template->load_def_from_script_and_call('preprocessing.php', 'init_preprocessing', $m, $c);
    };
?>