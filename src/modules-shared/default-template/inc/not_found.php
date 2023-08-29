<?
    $render_generic = function(Module $template, Target $root_target, array $params)
    {
        // Forward by setting sub_template := template
        $template->render_component_generic_with_sub_template($template, 'not_found', $root_target, $params);
    };

    $render_generic_with_sub_template = function(Module $template, Module $sub_template, Target $root_target, array $params)
    {
?>
<!doctype html>
<html lang="en">
    <head><title>Not found</title></head>
    <body style="background-color:rgb(52, 58, 64); color:rgb(33, 37, 41);">
        <div style="width: 300px; margin:20px auto; border-radius:4px; padding:20px; background-color:white;">
            Not found!
        </div>
    </body>
</html>
<? }; ?>