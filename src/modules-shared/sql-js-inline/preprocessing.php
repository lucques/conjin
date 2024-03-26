<?
    $init_preprocessing = function(Module $module, PreprocessContext $ctx) {
        $ctx->activate_module('js-standard-lib');
        $ctx->activate_module('sql-js');
    };
?>