<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-navigable');
        $c->activate_module('nav-build');
        $c->activate_module('issue-tracker');
    };
?>

<? $process = function (Target $target) { ?>

<?
    new Conjin\Modules\IssueTracker\Config(
        file_name: 'unused.sqlite',
        view_target_ids: [],
        tag_names: [],
        privilege_admin: 'unused-admin',
        privilege_write: 'unused-write',
        may_edit_own_content_within_n_minutes: -1,
        view: new Conjin\Modules\IssueTracker\ViewConfig(
            issue_column_width: '',
            tag_column_width: '',
            date_column_width: '',
            author_column_width: '',
            status_column_width: '',
            issue_desc_short: '',
            issue_desc_long: '',
            post_desc_short: '',
            post_desc_long: '',
            tag_desc: '',
            author_identity_to_label: fn(string $identity): string => '',
            status_to_variant: fn(string $status): string => '',
            status_to_label: fn(string $status): string => ''
        ),
        mail: null
    );
?>

<? }; ?>
