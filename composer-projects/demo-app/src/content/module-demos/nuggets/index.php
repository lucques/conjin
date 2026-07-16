<?
    use Conjin\Modules\Nuggets\ExamPrinter;

    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-exam', config: [
            'title' => 'Nuggets demo',
        ]);
        $c->activate_module('nav-build');
        $c->activate_module('mathjax-extensions');
        $c->activate_module('nuggets');
    };

    $process = function(Target $target) {
        $printer = ExamPrinter::make_default();
        $printer->print_exercise(path: 'demo');
    };
?>
