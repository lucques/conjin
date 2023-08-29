<?
    $init_preprocessing = function(Module $module, PreprocessContext $ctx) {
        $ctx->activate_module(new Module('jquery',                         is_shared: true, is_external: true));
        $ctx->activate_module(new Module('bootstrap',                      is_shared: true, is_external: true));
        $ctx->activate_module(new Module('prism',                          is_shared: true, is_external: true));
        $ctx->activate_module(new Module('mathjax',                        is_shared: true, is_external: true));
        $ctx->activate_module(new Module('mathjax-extensions',             is_shared: true, is_external: false));
        $ctx->activate_module(new Module('malihu-custom-scrollbar-plugin', is_shared: true, is_external: true));

        $ctx->activate_module(new Module('nav',                            is_shared: true, is_external: false));
    };
?>