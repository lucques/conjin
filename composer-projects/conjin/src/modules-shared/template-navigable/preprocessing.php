<?
    $init_preprocessing = function(Module $m, PreprocessContext $c) {
        $c->activate_module('nav-view');
        $c->activate_module('bootstrap-icons');
        $c->activate_module('bootstrap-extended', ['import_standalone_css' => false]);

        // Toggling
        $toggles = [];
        if ($m->config->get('sidebar') !== null) {
            $toggles[] = [
                'css_class' => 'sidebar-active',
                'on_by_default' => $m->config->get('sidebar', 'active_by_default'),
            ];

            $toggles[] = [
                'css_class' => 'reduced-nav',
                'on_by_default' => $m->config->get('sidebar', 'reduce_by_default'),
            ];
        }
        if ($m->config->get('login') !== null) {
            $toggles[] = [
                'css_class' => 'reduced-login',
                'on_by_default' => $m->config->get('login', 'reduce_by_default'),
            ];
        }
        $c->activate_module('dynamic-toggle', ['toggles' => $toggles]);
    }
?>