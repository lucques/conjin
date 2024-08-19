<?
    $init_preprocessing = function(Module $m, PreprocessContext $c) {      
        $c->activate_module('nav-view');  // The whole point of this template
        $c->activate_module('bootstrap'); // Nav design is based on bootstrap
        $c->activate_module('bootstrap-icons'); // Sidebar toolbar uses bootstrap icons
    };
?>