<?
    $profiles = [
        'generic' => function (SysletPreprocessContext $c) {
            $c->activate_template('template-generic');
        },
        'standalone' => function (SysletPreprocessContext $c) {
            $c->activate_template('template-navigable');
        },
    ];

    $default_profile_id = 'generic';
?>
