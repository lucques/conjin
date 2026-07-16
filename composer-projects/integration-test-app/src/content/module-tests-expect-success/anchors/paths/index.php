<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');

        $c->activate_module('anchors');
    };
?>

<?
    $process = function (Target $target) {
        $first_section_ids = anchor_create(2, 'first-section');
        $second_section_ids = anchor_create(2, 'second-section');
        $generated_subsection_ids = anchor_create_from_title(3, 'Generated subsection');
?>

<h2 id="<?= anchor_collect($first_section_ids) ?>" data-anchor-path="<?= htmlspecialchars(json_encode($first_section_ids), ENT_QUOTES) ?>">First section</h2>
<h2 id="<?= anchor_collect($second_section_ids) ?>" data-anchor-path="<?= htmlspecialchars(json_encode($second_section_ids), ENT_QUOTES) ?>">Second section</h2>
<h3 id="<?= anchor_collect($generated_subsection_ids) ?>" data-anchor-path="<?= htmlspecialchars(json_encode($generated_subsection_ids), ENT_QUOTES) ?>">Generated subsection</h3>

<?
    };
?>
