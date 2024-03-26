<?
    $init_preprocessing = function(Module $module, PreprocessContext $ctx) {
        $ctx->activate_module('jquery');
        $ctx->activate_module('bootstrap');
        $ctx->activate_module('mathjax');
        $ctx->activate_module('mathjax-extensions');
        $ctx->activate_module('malihu-custom-scrollbar-plugin');

        $ctx->activate_module('solution_mode');
        $ctx->activate_module('html');
        $ctx->activate_module('print_mode');
        $ctx->activate_module('accordion');

        $ctx->activate_module('nav');
    };
?>