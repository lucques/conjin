<?
    function issue_tracker_test_page(string $file_name, int $edit_window_minutes): void {
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['reset'] ?? null) === '1') {
            $path = path_store($file_name);
            if (file_exists($path)) {
                unlink($path);
            }
        }

        $view = new Conjin\Modules\IssueTracker\ViewConfig(
            issue_column_width: '4rem',
            tag_column_width: '10rem',
            date_column_width: '7rem',
            author_column_width: '8rem',
            status_column_width: '7rem',
            issue_desc_short: 'Issue',
            issue_desc_long: 'issue',
            post_desc_short: 'Post',
            post_desc_long: 'post',
            tag_desc: 'Tags',
            tag_desc_singular: 'Tag',
            solved_issue_count_desc: 'Solved issues',
            unsolved_issue_count_desc: 'Unsolved issues',
            author_identity_to_label: fn(string $identity): string => 'Test user',
            status_to_variant: fn(string $status): string => $status === 'open' ? 'warning' : 'success',
            status_to_label: fn(string $status): string => ucfirst($status)
        );
        $tracker = new Conjin\Modules\IssueTracker\IssueTracker(
            new Conjin\Modules\IssueTracker\Config(
                file_name: $file_name,
                view_target_ids: [],
                tags: [
                    [
                        'id' => 'bug',
                        'name' => 'Bug',
                        'client_data' => null,
                        'additional_email_addresses' => ['bugs@example.com']
                    ],
                    ['id' => 'docs', 'name' => 'Docs', 'client_data' => 'Configured client data']
                ],
                privilege_admin: 'issue-tracker-test-admin',
                privilege_write: 'issue-tracker-test-write',
                may_edit_own_content_within_n_minutes: $edit_window_minutes,
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

        $open_issues = $tracker->fetch_issues_with_posts_open();
        $closed_issues = $tracker->fetch_issues_with_posts_closed();
        $counts = $tracker->fetch_open_issue_counts_by_tag();
        $docs_tag_client_data = $tracker->fetch_tag_client_data('docs');
?>
<h1>Issue tracker test</h1>

<p id="docs-tag-client-data"><?= htmlspecialchars($docs_tag_client_data ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>

<dl id="open-counts">
<? foreach ($counts as $tag_name => $count) { ?>
    <dt><?= htmlspecialchars($tag_name) ?></dt>
    <dd data-tag="<?= htmlspecialchars($tag_name) ?>"><?= $count ?></dd>
<? } ?>
</dl>

<section id="tag-management">
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

<div id="new-issue">
    <? $tracker->print_new_issue_form($tags, $new_issue_res); ?>
</div>

<section id="open-issues">
    <h2>Open issues</h2>
<?
        $tracker->print_issues_as_acc(
            $open_issues,
            'No open issues.',
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
</section>

<section id="closed-issues">
    <h2>Closed issues</h2>
<?
        $tracker->print_issues_as_acc(
            $closed_issues,
            'No closed issues.',
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
</section>
<?
    }
?>
