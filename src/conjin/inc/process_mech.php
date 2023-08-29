<?
    // Main function
    function process(Target $target): void {

        //////////////////////////////
        // Run modules and template //
        //////////////////////////////

        foreach ($target->activated_modules as $name => $module) {
            $module->init_processing($target);
        }

        $target->template->init_processing($target);


        //////////////////
        // Pass-through //
        //////////////////

        $script_path = path_collect($target->ids) . '/index.php';
        $defs = load_defs_from_script($script_path);
        assert($defs['process'] !== null, 'Missing `$process` function');

        // First process only `$process` function: If this one fails, no need to process the header component before.
        ob_start();
        $defs['process']($target);
        $content = ob_get_clean();
        
        // Render header and footer and flush
        ob_start();
        $target->template->render_component_for_target('header', $target);
        echo $content;
        $target->template->render_component_for_target('footer', $target);
        ob_end_flush();
    }
?>