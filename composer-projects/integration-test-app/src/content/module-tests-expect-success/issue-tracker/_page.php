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
            author_identity_to_label: fn(string $identity): string => 'Test user',
            status_to_variant: fn(string $status): string => $status === 'open' ? 'warning' : 'success',
            status_to_label: fn(string $status): string => ucfirst($status)
        );
        $tracker = new Conjin\Modules\IssueTracker\IssueTracker(
            new Conjin\Modules\IssueTracker\Config(
                file_name: $file_name,
                view_target_ids: [],
                tag_names: ['Bug', 'Docs'],
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
        $edit_issue_res = $tracker->handle_edit_issue($tags);
        $edit_post_res = $tracker->handle_edit_post();
        $delete_issue_res = $tracker->handle_delete_issue();
        $delete_post_res = $tracker->handle_delete_post();

        $tracker->print_handle_new_issue_res($new_issue_res);
        $tracker->print_handle_new_post_res($new_post_res);
        $tracker->print_handle_edit_issue_res($edit_issue_res);
        $tracker->print_handle_edit_post_res($edit_post_res);
        $tracker->print_handle_delete_issue_res($delete_issue_res);
        $tracker->print_handle_delete_post_res($delete_post_res);

        $edit_issue_modal_id = $tracker->print_edit_issue_modal($tags);
        $edit_post_modal_id = $tracker->print_edit_post_modal($edit_post_res);
        $delete_issue_modal_id = $tracker->print_delete_issue_modal();
        $delete_post_modal_id = $tracker->print_delete_post_modal();

        $open_issues = $tracker->fetch_issues_with_posts_open();
        $closed_issues = $tracker->fetch_issues_with_posts_closed();
        $counts = $tracker->fetch_open_issue_counts_by_tag();
?>
<h1>Issue tracker test</h1>

<dl id="open-counts">
<? foreach ($counts as $tag_name => $count) { ?>
    <dt><?= htmlspecialchars($tag_name) ?></dt>
    <dd data-tag="<?= htmlspecialchars($tag_name) ?>"><?= $count ?></dd>
<? } ?>
</dl>

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
            $delete_post_modal_id
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
            $delete_post_modal_id
        );
?>
</section>
<?
    }
?>
