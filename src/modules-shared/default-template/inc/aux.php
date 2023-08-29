<?
    $print_favicons = function(Module $template)
    {
        if ($template->config->get_or_default(['shortcut_icons_enabled'], true))
        {
?>
        <link rel="shortcut icon"    type="image/png" sizes="16x16"   href="<?= $template->get_url() . '/res/favicon/favicon-16x16.png' ?>" />
        <link rel="shortcut icon"    type="image/png" sizes="32x32"   href="<?= $template->get_url() . '/res/favicon/favicon-32x32.png' ?>" />
        <link rel="shortcut icon"    type="image/png" sizes="64x64"   href="<?= $template->get_url() . '/res/favicon/favicon-64x64.png' ?>" />
        <link rel="shortcut icon"    type="image/png" sizes="96x96"   href="<?= $template->get_url() . '/res/favicon/favicon-96x96.png' ?>" />
        <link rel="shortcut icon"    type="image/png" sizes="128x128" href="<?= $template->get_url() . '/res/favicon/favicon-128x128.png' ?>" />
        <link rel="shortcut icon"    type="image/png" sizes="256x256" href="<?= $template->get_url() . '/res/favicon/favicon-256x256.png' ?>" />
        <link rel="apple-touch-icon" type="image/png" sizes="152x152" href="<?= $template->get_url() . '/res/favicon/favicon-apple-touch-icon-152x152.png' ?>" />
        <link rel="apple-touch-icon" type="image/png" sizes="167x167" href="<?= $template->get_url() . '/res/favicon/favicon-apple-touch-icon-167x167.png' ?>" />
        <link rel="apple-touch-icon" type="image/png" sizes="180x180" href="<?= $template->get_url() . '/res/favicon/favicon-apple-touch-icon-180x180.png' ?>" />
<?
        }
    };
?>