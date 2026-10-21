<?
use Conjin\Modules\FeedbackCollector as F;
require __DIR__ . '/php/fixtures/feedback.php';
require __DIR__ . '/php/fixtures/feedback-state.php';
try {
    $collector = new F\FeedbackCollector(sectioned_config(config(anonymity: F\Anonymity::Optional)));
    echo '<!doctype html><meta charset="utf-8"><title>Feedback test</title>' . render($collector)
        . '<script>' . file_get_contents(dirname(__DIR__) . '/composer-projects/conjin/src/modules-shared/feedback-collector/res/form.js') . '</script>';
}
finally {
    unlink($GLOBALS['feedback_test_db']);
}
