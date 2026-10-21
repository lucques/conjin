<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');
        $c->activate_module('issue-tracker');
    };
?>

<? $process = function (Target $target) { ?>

<?
    $view = new Conjin\Modules\IssueTracker\ViewConfig(
        issue_column_width: '40px',
        tag_column_width: '290px',
        date_column_width: '100px',
        author_column_width: '120px',
        status_column_width: '120px',
        issue_desc_short: 'Anliegen',
        issue_desc_long: 'Anliegen',
        post_desc_short: 'Beitrag',
        post_desc_long: 'Beitrag',
        tag_desc: 'Bereiche',
        tag_desc_singular: 'Bereich',
        solved_issue_count_desc: 'Anzahl erledigter Anliegen',
        unsolved_issue_count_desc: 'Anzahl offener Anliegen',
        author_identity_to_label: fn(string $identity): string => $identity,
        status_to_variant: fn(string $status): string => $status === 'open' ? 'warning' : 'success',
        status_to_label: fn(string $status): string => $status === 'open' ? 'Offen' : 'Erledigt'
    );
    $tracker = new Conjin\Modules\IssueTracker\IssueTracker(
        new Conjin\Modules\IssueTracker\Config(
            file_name: 'issue-tracker-demo.sqlite',
            view_target_ids: [],
            tags: [
                [
                    'id' => 'inhalt',
                    'name' => 'Inhalt',
                    'client_data' => null,
                    'additional_email_addresses' => ['inhalt@example.com']
                ],
                ['id' => 'darstellung', 'name' => 'Darstellung', 'client_data' => null],
                ['id' => 'technik', 'name' => 'Technik', 'client_data' => null]
            ],
            privilege_admin: 'administer-issue-tracker-demo',
            privilege_write: 'administer-issue-tracker-demo',
            may_edit_own_content_within_n_minutes: 0,
            view: $view,
            mail: null
        )
    );
    $tags = $tracker->fetch_tags();

    $new_issue_res = $tracker->handle_new_issue($tags);
    $new_post_res = $tracker->handle_new_post();
    $new_additional_email_res = $tracker->handle_new_additional_email();
    $new_tag_res = $tracker->handle_new_tag();
    $edit_tag_res = $tracker->handle_edit_tag();
    $mail_tags_res = $tracker->handle_mail_tags();
    $edit_issue_res = $tracker->handle_edit_issue($tags);
    $edit_post_res = $tracker->handle_edit_post();
    $delete_issue_res = $tracker->handle_delete_issue();
    $delete_post_res = $tracker->handle_delete_post();
    $delete_additional_email_res = $tracker->handle_delete_additional_email();
    $tags = $tracker->fetch_tags();
?>

<h1>issue-tracker</h1>

<p>This is a persistent tracker. Administration and new content are restricted to the root user.</p>

<?
    $tracker->print_handle_new_issue_res($new_issue_res);
    $tracker->print_handle_new_post_res($new_post_res);
    $tracker->print_handle_new_additional_email_res($new_additional_email_res);
    $tracker->print_handle_new_tag_res($new_tag_res);
    $tracker->print_handle_edit_tag_res($edit_tag_res);
    $tracker->print_handle_mail_tags_res($mail_tags_res);
    $tracker->print_handle_edit_issue_res($edit_issue_res);
    $tracker->print_handle_edit_post_res($edit_post_res);
    $tracker->print_handle_delete_issue_res($delete_issue_res);
    $tracker->print_handle_delete_post_res($delete_post_res);
    $tracker->print_handle_delete_additional_email_res($delete_additional_email_res);

    $edit_issue_modal_id = $tracker->print_edit_issue_modal($tags);
    $edit_post_modal_id = $tracker->print_edit_post_modal($edit_post_res);
    $delete_issue_modal_id = $tracker->print_delete_issue_modal();
    $delete_post_modal_id = $tracker->print_delete_post_modal();
    $delete_additional_email_modal_id = $tracker->print_delete_additional_email_modal();
    $new_tag_modal_id = $tracker->print_new_tag_modal($new_tag_res);
    $edit_tag_modal_id = $tracker->print_edit_tag_modal($edit_tag_res);
    $mail_tags_modal_id = $tracker->print_mail_tags_modal($mail_tags_res);
?>

<section class="mb-4">
<?
    $tag_desc = htmlspecialchars($tracker->config->view->tag_desc, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $tag_management_open = $_SERVER['REQUEST_METHOD'] === 'POST' &&
        is_string($_POST['req'] ?? null) &&
        in_array($_POST['req'], ['new-tag', 'edit-tag'], true);
?>
    <h2><?= $tag_desc ?> verwalten</h2>
<?
    acc_start();
    acc_item_start('Alle ' . $tag_desc, open: $tag_management_open);
    $tracker->print_tag_management($tracker->fetch_tags_with_issue_counts(), $new_tag_modal_id, $edit_tag_modal_id, $mail_tags_modal_id);
    acc_item_end();
    acc_end();
?>
</section>

<div class="mb-4">
    <? $tracker->print_new_issue_form($tags, $new_issue_res); ?>
</div>

<h2>Open issues</h2>

<?
    $tracker->print_issues_as_acc(
        $tracker->fetch_issues_with_posts_open(),
        'There are no open issues.',
        $new_post_res,
        $edit_issue_res,
        $edit_post_res,
        $delete_issue_res,
        $delete_post_res,
        $edit_issue_modal_id,
        $edit_post_modal_id,
        $delete_issue_modal_id,
        $delete_post_modal_id,
        $new_additional_email_res,
        $delete_additional_email_res,
        $delete_additional_email_modal_id
    );
?>

<h2>Closed issues</h2>

<?
    $tracker->print_issues_as_acc(
        $tracker->fetch_issues_with_posts_closed(),
        'There are no closed issues.',
        $new_post_res,
        $edit_issue_res,
        $edit_post_res,
        $delete_issue_res,
        $delete_post_res,
        $edit_issue_modal_id,
        $edit_post_modal_id,
        $delete_issue_modal_id,
        $delete_post_modal_id,
        $new_additional_email_res,
        $delete_additional_email_res,
        $delete_additional_email_modal_id
    );
?>

<? }; ?>
