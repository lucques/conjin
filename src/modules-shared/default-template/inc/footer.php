<?
    $render_for_target = function(Module $template, Target $target)
    {
        // Forward by setting sub_template := template
        $template->render_component_for_target_with_sub_template($template, 'footer', $target);
    };

    $render_for_target_with_sub_template = function(Module $template, Module $sub_template, Target $target)
    {
        if ($target->has_activated_module('print_mode')) { page_end(); };
?>

            </main>
        </div>
        <script>
            const NAV_SHOW_TOP_LEVEL              = <?= $template->config->get_or_default(['nav_show_top_level'], true) ? 'true' : 'false' ?>;
            const NAV_ACTIVE_SIDEBAR_BY_DEFAULT   = <?= $template->config->get_or_default(['nav_active_sidebar_by_default'], true) ? 'true' : 'false' ?>;
            const NAV_SHOW_LEVEL_0                = <?= $template->config->get_or_default(['nav_show_level_0'], true) ? 'true' : 'false' ?>;
            const NAV_REDUCE_BY_DEFAULT           = <?= $template->config->get_or_default(['nav_reduce_by_default'], true) ? 'true' : 'false' ?>;
            const NAV_REDUCE_TOGGLEABLE_WITH_CTRL = <?= $template->config->get_or_default(['nav_reduce_toggleable_with_ctrl'], true) ? 'true' : 'false' ?>;

            setupAfterScriptsLoaded();
            document.addEventListener('DOMContentLoaded', setupAfterDOMLoaded);
        </script>
    </body>
</html>
<?
    };
?>