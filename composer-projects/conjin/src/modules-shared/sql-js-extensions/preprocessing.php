<?
    //////////
    // Init //
    //////////

    $preprocess_target = $preprocess_syslet = function(Module $m, PreprocessContext $c) {
        $c->activate_module('js-standard-lib');
        $c->activate_module('sql-js');
    };
?>