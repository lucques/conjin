<?
    $render = function(Module $template, array $placeholders) {
        $module_sol_mode_active   = function_exists('is_sol_mode_on');
        $module_print_mode_active = function_exists('is_print_mode_on');
?>
        <div id="sidebar">
            <div id="sidebar-header">
<?
        $logo = null;
        $logo_class = null;
        if ($template->config->get('sidebar', 'use_img_as_logo')) {
            $logo .= '<img src="' . $placeholders['logo_url'] . '" alt="' . $placeholders['title_for_logo'] . '" height="200">';
            $logo_class = 'logo-img';
        }
        else {
            $logo .= $placeholders['title_for_logo'];
            $logo_class = 'logo-text';
        }

        if ($template->config->get('sidebar', 'link_logo_to_home')) {
           $logo = '<a id="logo" class="' . $logo_class . '" href="' . url_collect() . '">' . $logo . '</a>';
        }
        else {
           $logo = '<span id="logo" class="' . $logo_class . '">' . $logo . '</span>';
        }
?>
                <?= $logo ?>

                <div id="sidebar-buttons">
<?
        if ($module_sol_mode_active && is_sol_mode_allowed()) {
            if (is_sol_mode_on()) {
?>
                    <button type="button" class="btn btn-primary active" data-bs-toggle="tooltip" data-bs-title="Lösung ausblenden" data-bs-placement="bottom" onclick="window.location.href = './<?= make_query_string(sol_mode_get_queries_to_request_off(), base_queries: $_GET) ?>';">
<?
            }
            else {
?>
                    <button type="button" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-title="Lösung einblenden" data-bs-placement="bottom" onclick="window.location.href = './<?= make_query_string(sol_mode_get_queries_to_request_on(), base_queries: $_GET) ?>';">
<?
            }
?>
                        <i class="bi-lightbulb"></i>
                    </button>
<?
        }
        if ($module_print_mode_active && !is_print_mode_always_on()) {
            if (is_print_mode_on()) {
?>
                    <a href="./<?= make_query_string(print_mode_get_query_string_to_request_off(), base_queries: $_GET) ?>" class="btn btn-primary active" data-bs-toggle="tooltip" data-bs-title="Druckversion deaktivieren" data-bs-placement="bottom">
<?
            }
            else {
?>
                    <a href="./<?= make_query_string(print_mode_get_query_string_to_request_on(), base_queries: $_GET) ?>" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-title="Druckversion aktivieren" data-bs-placement="bottom">
<?
            }
?>
                        <i class="bi-printer"></i>
                    </a>
<?
        }
?>
                    <button id="sidebar-collapse-button-activate" type="button" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-layout-sidebar" viewBox="0 0 16 16"><path d="M0 3a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm5-1v12h9a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1zM4 2H2a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h2z"/></svg>
                    </button>
                    <button id="sidebar-collapse-button-deactivate" type="button" class="btn-close" aria-label="Close"></button>
<?
        // Setup button to activate/deactive sidebar
        doc_extensions_add_js_after_dom_setup("document.querySelector('#sidebar-collapse-button-activate').addEventListener('click', event => { dtToggle('sidebar-active'); });");
        doc_extensions_add_js_after_dom_setup("document.querySelector('#sidebar-collapse-button-deactivate').addEventListener('click', event => { dtToggle('sidebar-active'); });");
?>
                </div>
            </div>
            <div id="sidebar-nav">
<?
        nav_print('sidebar-nav-breadcrumb', 'sidebar-nav-tree', $template->config->get('sidebar', 'reduce_breadcrumb_up_to_level'));

        // Setup ctrl key to reduce nav
        if ($template->config->get('sidebar', 'reduce_toggleable_with_ctrl')) {
            doc_extensions_add_js_after_dom_setup("document.querySelector('#sidebar-nav').addEventListener('click', event => { if (event.ctrlKey) { dtToggle('reduced-nav'); } });");
        }
?>
            </div>
            <div id="sidebar-footer">
<?
        if (auth_is_logged_in()) {
?>
                <span id="sidebar-footer-user"><?= auth_get_user_name() ?></span>
                <span id="sidebar-footer-actions">
                    &nbsp;| <a href="<?= auth_get_logout_url() ?>">Logout</a>
                </span>
<?
        }
        else {
?>
                <span id="sidebar-footer-actions">
                    <a href="<?= auth_get_login_url_with_redirect() ?>">Login</a>
                </span>
<?
        }
?>
            </div>
        </div>
<?
    };
?>