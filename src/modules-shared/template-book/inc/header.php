<?
    $render_for_target = function(Module $template, Target $target)
    {
        // Forward by setting sub_template := template
        $template->render_component_for_target_with_sub_template($template, 'header', $target);
    };

    $render_for_target_with_sub_template = function(Module $template, Module $sub_template, Target $target)
    {
        $title = null;
        if ($template->config->isset('content_title')) {
            $title = $template->config->get('content_title');
        }
        else {
            $number_of_visible_parent_titles = min($template->config->get_or_default(['number_of_visible_parent_titles'], 0), count($target->all_titles) - 1);
            $title = implode(' » ', array_slice($target->all_titles, count($target->all_titles) - $number_of_visible_parent_titles - 1));
        }
        
        $head_title = count($target->all_titles) == 1 ? $title : $title . ' − ' . $target->all_titles[0];
?>
<!doctype html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= $head_title ?></title>
<?
        if (!$target->has_activated_module('print_mode') || !is_print_mode_active()) {
?>
        <link href="<?= $template->get_css_url() . '/screen.css' ?>" rel="stylesheet" media="screen">
        <link href="<?= $template->get_css_url() . '/print.css'  ?>" rel="stylesheet" media="print">
<?
        }
        else {
            // This is only the template-specific part of the print mode CSS.
            // Another part is included by the "print_mode" module.
?>
        <link href="<?= $template->get_css_url() . '/print.css'  ?>" rel="stylesheet" media="all">
<?
        }
?>
        <script src="<?= $sub_template->get_url() . '/res/js/script.js' ?>"></script>
<?
        if ($target->has_activated_module('doc_extensions')) {
            array_map(function($extension) {
                echo '        ' . str_replace("\n", "\n        ", $extension) . "\n";
            }, doc_extensions_get_head_extensions());
        }

        load_defs_from_script(__DIR__ . '/aux.php')['print_favicons']($template);
?>
    </head>
<?
        $body_classes = $target->has_activated_module('doc_extensions') ? doc_extensions_get_body_classes() : [];
?>
    <body id="target_<?= $target->get_css_slug() ?>" data-target-ids="<?= implode(' ', $target->ids) ?>" class="<?= implode(' ', $body_classes) ?>" data-bs-spy="scroll" data-bs-target=".list-level-0" data-bs-offset="30">
        <div id="sidebar">
            <div id="sidebar-header">
<?
        $logo = null;
        $logo_class = null;
        if ($template->config->get_or_default(['use_img_as_logo'], false)) {
            $logo .= '<img src="' . $template->get_url() . '/res/logo/logo.png" alt="">';
            $logo_class = 'logo-img';
        }
        else {
            $logo .= $target->all_titles[0];
            $logo_class = 'logo-text';
        }

        if ($template->config->get_or_default(['link_logo_to_home'], true)) {
           $logo = '<a id="logo" class="' . $logo_class . '" href="' . url_collect() . '">' . $logo . '</a>';
        }
        else {
           $logo = '<span id="logo" class="' . $logo_class . '">' . $logo . '</span>';
        }
?>
                <?= $logo ?>
                <div id="sidebar-buttons">
<?
        if ($target->has_activated_module('solution_mode') && is_solution_mode_enabled()) {
            if (is_solution_mode_active()) {
?>
                    <a class="btn btn-primary active" href="./<?= make_query_string(solution_mode_get_queries_to_deactivate(), base_queries: $_GET) ?>" role="button">Lösung</a>
<?
            }
            else {
?>
                    <a class="btn btn-primary" href="./<?= make_query_string(solution_mode_get_queries_to_activate(), base_queries: $_GET) ?>" role="button">Lösung</a>
<?
            }
        }
?>
<?
        if ($target->has_activated_module('print_mode') && is_print_mode_enabled()) {
?>
                    <a class="btn btn-primary" href="./<?= make_query_string(print_mode_get_query_string_to_activate(), base_queries: $_GET) ?>" role="button">🖨</a>
<?
        }
?>
                    <button type="button" id="sidebar-collapse-button" class="btn btn-primary">
                        <span id="sidebar-collapse-button-activate">→</span>
                        <span id="sidebar-collapse-button-deactivate">←</span>
                    </button>
                </div>
            </div>
            <nav id="nav">
<?
        nav_print();
?>
            </nav>
<?
        if (auth_is_enabled()) {
?>
            <div id="sidebar-footer">
<?
            if (auth_is_logged_in()) {
?>
                <?= auth_get_user() ?> | <a href="<?= auth_get_logout_url() ?>">Logout</a>
<?
            }
            else {
?>
                <a href="<?= auth_get_login_url_with_redirect() ?>">Login</a>
<?
            }
?>
            </div>
<?
        }
?>
        </div>
        <div id="content">
            <main>
<? if ($target->has_activated_module('print_mode')) { page_start(); }; ?>

<?
        if ($template->config->get_or_default(['content_print_title'], true)) {
?>
                <h1><?= $title ?></h1>
<?
        }

    };
?>