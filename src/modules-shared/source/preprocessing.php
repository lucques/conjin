<?
    $init_preprocessing = function(Module $module, PreprocessContext $ctx) {
        $ctx->activate_module('doc_extensions');
        $ctx->activate_module('prism');
    };
?>