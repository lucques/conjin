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
        author_identity_to_label: fn(string $identity): string => $identity,
        status_to_variant: fn(string $status): string => $status === 'open' ? 'warning' : 'success',
        status_to_label: fn(string $status): string => $status === 'open' ? 'Offen' : 'Erledigt'
    );
    $tracker = new Conjin\Modules\IssueTracker\IssueTracker(
        new Conjin\Modules\IssueTracker\Config(
            file_name: 'issue-tracker-demo.sqlite',
            view_target_ids: [],
            tag_names: ['Inhalt', 'Darstellung', 'Technik'],
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
    $edit_issue_res = $tracker->handle_edit_issue($tags);
    $edit_post_res = $tracker->handle_edit_post();
    $delete_issue_res = $tracker->handle_delete_issue();
    $delete_post_res = $tracker->handle_delete_post();
?>

<h1>issue-tracker</h1>

<p>This is a persistent tracker. Administration and new content are restricted to the root user.</p>

<?
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
?>

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
        $delete_post_modal_id
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
        $delete_post_modal_id
    );
?>

<? }; ?>
