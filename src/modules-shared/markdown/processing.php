<?
    //////////////////
    // Dependencies //
    //////////////////

    $parseDownModule = new ModuleLocation('parsedown');


    //////////
    // Init //
    //////////

    $init_processing_target = function(Module $module, Target $target) use ($parseDownModule) {
        require_once($parseDownModule->get_path() . '/src/Parsedown.php');
    };


    /////////////////////
    // Used by CONTENT //
    /////////////////////

    function md_start(): void
    {
        ob_start();
    }

    function md_end(): void
    {
        $content = ob_get_clean();
        echo Parsedown::instance()->text($content);
    }
?>