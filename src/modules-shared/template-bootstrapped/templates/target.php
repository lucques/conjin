<?
    $render_target = function(Module $template, Target $target, string $content, array $placeholders_overrides = []) {

        //////////////////////
        // Determine titles //
        //////////////////////

        $title_for_head =
            $target->has_activated_module('title')
            ? implode(' » ', get_plain_titles(true))
            : ($target->id ?? 'Unbenannt');


        //////////////////////////
        // Prepare placeholders //
        //////////////////////////

        $placeholders_default = [
            'css_url'        => $template->get_css_url(),
            
            'title_for_head' => $title_for_head,
        ];
        $placeholders = array_merge($placeholders_default, $placeholders_overrides);
        
        
        ////////////
        // Render //
        ////////////
        
        ob_start();
        $template->load_def_from_script_and_call('templates/inc/header.php', 'render', $placeholders);
        echo $content;
        $template->load_def_from_script_and_call('templates/inc/footer.php', 'render');
        ob_end_flush();
    };
?>