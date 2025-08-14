<?
    $render_target = function(ModuleLocation $template_self, Module $template, Target $target, string $content, array $placeholders_overrides = []) {

        //////////////////////////
        // Prepare placeholders //
        //////////////////////////

        $title_for_head = $target->id ?? 'Unbenannt';

        $date_str = $template->config->get('date_default');
        $date = iso_to_date(substr($target->id, 0, 10));
        if ($date != null) {
            $date_str = $date->format('d.m.Y');
        }
    

        ///////////////////////
        // Make placeholders //
        ///////////////////////

        $placeholders_default = [
            'institution'  => $template->config->get('institution'),
            'class'        => $template->config->get('class'),
            'date'         => $date_str,
            'title'        => $template->config->get('title'),
        ];
        $placeholders = array_merge($placeholders_default, $placeholders_overrides);


        ////////////////////
        // Render content //
        ////////////////////

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


        ///////////////////////////
        // Sub-template: Prepare //
        ///////////////////////////

        $sub_template = new ModuleLocation('template-navigable');
        $placeholders_for_subtemplate_default = $template_self->load_def_from_script_and_call(
            'templates/inc/overridden_placeholders_for_subtemplate.php',
            'default_placeholders',
            template:       $template,
            sub_template:   $sub_template
        );
        $placeholders_for_subtemplate = array_merge($placeholders_for_subtemplate_default, $placeholders_overrides);


        //////////////////////////
        // Sub-template: Render //
        //////////////////////////

        $sub_template->render_target_with_provided_template($template, $target, $content, $placeholders_for_subtemplate);
    };
?>