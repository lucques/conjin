<?
    $render_target = function(ModuleLocation $template_self, Module $template, Target $target, string $content, array $placeholders_overrides = []) {

        //////////////////////////
        // Prepare placeholders //
        //////////////////////////

        $title_for_head =
            $target->has_activated_module('title')
            ? ($target->has_parent()
                ? implode(' » ', get_plain_titles(true)) . ($template->config->get('title_for_head_contains_top_level') ? ' − ' . get_top_level_plain_title() : '')
                : get_top_level_plain_title())
            : ($target->id ?? 'Unbenannt');


        ///////////////////////
        // Make placeholders //
        ///////////////////////

        $placeholders_default = $template_self->load_def_from_script_and_call(
            'templates/inc/default_placeholders.php',
            'default_placeholders',
            $template,
            $target,
            $title_for_head
        );
        $placeholders = array_merge($placeholders_default, $placeholders_overrides);


        ////////////
        // Render //
        ////////////

        $module_print_mode_active = $target->has_activated_module('print-mode');
        $print_mode_standalone =
            $module_print_mode_active
            && ($_GET['wrapper'] ?? '1') === '0';

        if ($print_mode_standalone) {
            print_mode_prepare_document($target);
            $template_self->load_def_from_script_and_call(
                'templates/inc/standalone.php',
                'render',
                template:       $template,
                target:         $target,
                content:        $content,
                title_for_head: $title_for_head,
                placeholders:   $placeholders
            );
        }
        else {
            if ($module_print_mode_active) {
                $iframe_url = './' . make_query_string(
                    ['wrapper' => '0'],
                    base_queries: $_GET
                );
                ob_start();
?>
                <iframe id="print-mode-iframe" src="<?= htmlspecialchars($iframe_url, ENT_QUOTES) ?>" title="Druckvorschau"></iframe>
                <script>
                    (() => {
                        const iframe = document.querySelector('#print-mode-iframe');
                        const printIframe = () => {
                            if (iframe.contentWindow.printModeReady) {
                                iframe.contentWindow.print();
                            }
                            else {
                                iframe.contentWindow.addEventListener(
                                    'print-mode-ready',
                                    () => iframe.contentWindow.print(),
                                    { once: true }
                                );
                            }
                        };
                        const handlePrintShortcut = event => {
                            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'p') {
                                event.preventDefault();
                                printIframe();
                            }
                        };
                        const setupIframeShortcut = () => {
                            iframe.contentDocument.addEventListener('keydown', handlePrintShortcut);
                        };

                        document.querySelector('#print-mode-print-button').addEventListener('click', printIframe);
                        document.addEventListener('keydown', handlePrintShortcut);
                        setupIframeShortcut();
                        iframe.addEventListener('load', setupIframeShortcut);
                    })();
                </script>
<?
                $content = ob_get_clean();
                $placeholders['body_classes'][] = 'print-mode-wrapper';
                $placeholders['include_print_stylesheet'] = false;
                $placeholders['show_print_button'] = true;
            }

            ob_start();
            $template_self->load_def_from_script_and_call('templates/inc/header-1.php', 'render', $template, $target, $target->get_ids(), $placeholders);
            if ($template->config->get('sidebar') !== null) {
                $template_self->load_def_from_script_and_call('templates/inc/sidebar.php', 'render', $template, $target, $placeholders);
            }
            $template_self->load_def_from_script_and_call('templates/inc/header-2.php', 'render', $template, $placeholders);
            echo $content;
            $template_self->load_def_from_script_and_call('templates/inc/footer.php', 'render', $template, $target, $placeholders);
            ob_end_flush();
        }
    };
?>
