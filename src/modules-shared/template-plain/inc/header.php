<?
    $render_for_target = function(Module $template, Target $target)
    {
?>
<!doctype html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <title><?= $target->all_titles[count($target->all_titles) - 1] ?></title>
<?
        if ($target->has_activated_module('doc_extensions')) {
            array_map(function($extension) {
                echo '        ' . str_replace("\n", "\n        ", $extension) . "\n";
            }, doc_extensions_get_head_extensions());
        }
?>
    </head>
<?
        $body_classes = $target->has_activated_module('doc_extensions') ? doc_extensions_get_body_classes() : [];
?>
    <body<?= count($body_classes) > 0 ? ' class="' . implode(' ', $body_classes) . '"' : '' ?>>
<?
    };
?>