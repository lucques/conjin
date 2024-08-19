<?
    $render_target = function(Module $template, Target $target, string $content, array $placeholders_overrides = []) {

        //////////////////////
        // Determine titles //
        //////////////////////

        $title_for_logo =
            $target->has_activated_module('title')
            ? get_top_level_html_title()
            : 'Unbenannt';
        $title_for_head = $target->id ?? 'Unbenannt';        
        $title_for_h1 =
            $target->has_activated_module('title')
            ? implode(' » ', get_html_titles(true))
            : ($target->id ?? 'Unbenannt');

        $date_str = $template->config->get('date_default');
        $date = iso_to_date(substr($target->id, 0, 10));
        if ($date != null) {
            $date_str = $date->format('d.m.Y');
        }
    

        //////////////////////////
        // Prepare placeholders //
        //////////////////////////

        $placeholders_default = [
            'institution'  => $template->config->get('institution'),
            'class'        => $template->config->get('class'),
            'date'         => $date_str,
            'title'        => $template->config->get('title'),
        ];
        $placeholders = array_merge($placeholders_default, $placeholders_overrides);


        ///////////////////////////////////////////////
        // Prepare sub-template `template-navigable` //
        ///////////////////////////////////////////////
        
        $placeholders_for_subtemplate_default = [
            'url'                             => $template->get_url(),
            'css_url'                         => $template->get_css_url(),

            'title_for_logo'                  => $title_for_logo,
            'title_for_head'                  => $title_for_head,

            'use_img_as_logo'                 => $template->config->get('use_img_as_logo'),
            'link_logo_to_home'               => $template->config->get('link_logo_to_home'),
            'nav_show_top_level'              => $template->config->get('nav_show_top_level'),
            'nav_active_sidebar_by_default'   => $template->config->get('nav_active_sidebar_by_default'),
            'nav_reduce_by_default'           => $template->config->get('nav_reduce_by_default'),
            'nav_reduce_toggleable_with_ctrl' => $template->config->get('nav_reduce_toggleable_with_ctrl'),
        ];
        $placeholders_for_subtemplate = array_merge($placeholders_for_subtemplate_default, $placeholders_overrides);
        $sub_template = $target->activated_modules['template-navigable'];
        

        ////////////
        // Render //
        ////////////

        // Render content
        ob_start();
?>
<div id="eheader">
    <div id="eheader-left">
        <?= $placeholders['institution'] ?>, <?= $placeholders['class'] ?>
    </div>
    <div id="eheader-center">
        <em>Name:</em> _________________________________
    </div>
    <div id="eheader-right">
        <?= $placeholders['date'] ?>
    </div>
</div>

<div id="etitle">
    <h1><?= $placeholders['title'] ?></h1>
</div>
<?
        $content = ob_get_clean() . $content;

        // Render using sub-template
        $sub_template->render_target($target, $content, $placeholders_for_subtemplate);
    };
?>