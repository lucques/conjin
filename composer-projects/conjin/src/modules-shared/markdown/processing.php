<?
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
