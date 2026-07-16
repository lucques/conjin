<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');

        // nav-build already depends on anchors; activating it explicitly makes the
        // module under demonstration visible at the target level as well.
        $c->activate_module('anchors');

        $c->activate_module('title');
        $c->run_macro('title', 'set', 'anchors');
    };
?>

<? $process = function (Target $target) { ?>

<p>
    The <code>anchors</code> module builds a semantic anchor tree from a linear sequence of calls. Each creation function returns the resulting anchor path; <code>anchor_collect()</code> turns that path into the corresponding HTML ID.
</p>
<p>
    Each heading below has a permanent link. Select the <code>#</code> beside a heading, then bookmark, copy, or share the resulting URL.
</p>

<?
    $first_section_ids = anchor_create(2, 'first-section');
    $first_section_id = anchor_collect($first_section_ids);
?>
<h2 id="<?= $first_section_id ?>">
    First section
    <a href="#<?= $first_section_id ?>" class="text-decoration-none" aria-label="Permanent link to First section" title="Link to this section">#</a>
</h2>
<p>
    <code>anchor_create(2, 'first-section')</code> creates the path <code><?= json_encode($first_section_ids) ?></code> and the HTML ID <code><?= $first_section_id ?></code>.
</p>

<?
    $second_section_ids = anchor_create(2, 'second-section');
    $second_section_id = anchor_collect($second_section_ids);
?>
<h2 id="<?= $second_section_id ?>">
    Second section
    <a href="#<?= $second_section_id ?>" class="text-decoration-none" aria-label="Permanent link to Second section" title="Link to this section">#</a>
</h2>
<p>
    Another level-two anchor becomes a sibling: <code><?= json_encode($second_section_ids) ?></code>.
</p>

<?
    $generated_subsection_ids = anchor_create_from_title(3, 'Generated subsection');
    $generated_subsection_id = anchor_collect($generated_subsection_ids);
?>
<h3 id="<?= $generated_subsection_id ?>">
    Generated subsection
    <a href="#<?= $generated_subsection_id ?>" class="text-decoration-none" aria-label="Permanent link to Generated subsection" title="Link to this section">#</a>
</h3>
<p>
    <code>anchor_create_from_title(3, 'Generated subsection')</code> derives the semantic ID and nests it below the current level-two anchor. The resulting path is <code><?= json_encode($generated_subsection_ids) ?></code>, collected as <code><?= $generated_subsection_id ?></code>.
</p>

<?
    $explicit_subsection_ids = anchor_create(3, 'explicit-subsection');
    $explicit_subsection_id = anchor_collect($explicit_subsection_ids);
?>
<h3 id="<?= $explicit_subsection_id ?>">
    Explicit subsection
    <a href="#<?= $explicit_subsection_id ?>" class="text-decoration-none" aria-label="Permanent link to Explicit subsection" title="Link to this section">#</a>
</h3>
<p>
    Creating another level-three anchor returns to the existing parent and creates a sibling: <code><?= json_encode($explicit_subsection_ids) ?></code>.
</p>

<?
    $top_level_ids = anchor_create_from_title(2, 'Back at the top level');
    $top_level_id = anchor_collect($top_level_ids);
?>
<h2 id="<?= $top_level_id ?>">
    Back at the top level
    <a href="#<?= $top_level_id ?>" class="text-decoration-none" aria-label="Permanent link to Back at the top level" title="Link to this section">#</a>
</h2>
<p>
    Moving back to level two drops the previous parent path: <code><?= json_encode($top_level_ids) ?></code>.
</p>

<? }; ?>
