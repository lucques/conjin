<?
    $_SESSION = [];
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $GLOBALS['feedback_test_db'] = tempnam(sys_get_temp_dir(), 'feedback-test-');
    $GLOBALS['feedback_test_logged_in'] = true;
    $GLOBALS['feedback_test_privileged'] = true;
    $GLOBALS['feedback_test_user'] = 'teacher';
    $GLOBALS['feedback_test_mail_config'] = true;
    $GLOBALS['feedback_test_logs'] = [];
    $GLOBALS['feedback_test_author_email'] = 'teacher-private@example.test';
    $GLOBALS['feedback_test_email_reads'] = 0;
    $GLOBALS['feedback_test_label_reads'] = 0;
