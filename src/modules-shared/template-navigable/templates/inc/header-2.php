<?
    $render = function(Module $template, array $placeholders) {
        $module_print_mode_active     = function_exists('is_print_mode_on');
?>
        <div id="content">
            <main>
<? if ($module_print_mode_active) { page_start(); }; ?>
<?
    };
?>