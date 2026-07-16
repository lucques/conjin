<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');
        
        $c->activate_module('bootstrap-alternative', [
            'primary' => '#6f42c1',
            'primary_rgb' => '111, 66, 193',
        ]);
    };
?>

<? $process = function (Target $target) { ?>

<h1>bootstrap-alternative</h1>
<p>This target activates <code>bootstrap-alternative</code> without Bootstrap or <code>bootstrap-extended</code>. Its configured primary color is purple, so every primary component, focus ring, subtle background, and border below uses the target-level color override.</p>

<h2>Accordions</h2>

<? acc_start(only_one_open: true, id: 'exclusive-accordion'); ?>
<? acc_item_start('Initially open item', open: true, id: 'exclusive-first'); ?>
<p>Opening another item closes this one because <code>only_one_open</code> is enabled.</p>
<? acc_item_end(); ?>
<? acc_item_start('Item with a modal action', header_actions: '<button type="button" class="accordion-header-action" data-bs-toggle="modal" data-bs-target="#feature-modal" aria-label="Open modal">&#x2197;</button>'); ?>
<p>The separate header action opens the module's modal while the title toggles this item.</p>
<? acc_item_end(); ?>
<? acc_item_start('Hidden item', hidden: true); ?>
<p>This content is deliberately discarded.</p>
<? acc_item_end(); ?>
<? acc_end(); ?>

<? acc_start(variant: 'success', class: 'mt-3', style: '--demo-pass-through: 1;'); ?>
<? acc_block_start('Permanent block', bg_as_collapsed: true); ?>
<p>Blocks are always visible and can use the collapsed header background.</p>
<? acc_block_end(); ?>
<? acc_item_start('Nested accordion'); ?>
<? ob_start(); acc_heading(3, 'Nested item'); $nested_title = ob_get_clean(); ?>
<? acc_single_item_start($nested_title, variant: 'info', open: true); ?>
<p>Nested macro calls receive stable generated IDs.</p>
<? acc_single_item_end(); ?>
<? acc_item_end(); ?>
<? acc_end(); ?>

<? acc_single_block_start('Single-block shorthand', variant: 'warning'); ?>
<p><code>acc_single_block_start()</code> and <code>acc_single_block_end()</code> wrap a standalone permanent block.</p>
<? acc_single_block_end(); ?>

<? ob_start(); acc_heading(2, 'Header-only shorthand'); $header_only_title = ob_get_clean(); ?>
<? acc_header_only($header_only_title, variant: 'dark', bg_as_collapsed: true, acc_id: 'header-only'); ?>

<h3>Every variant accepted by <code>variant_to_color()</code></h3>
<?
    $accordion_variants = [
        'primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark',
        'orange', 'green', 'blue', 'definition', 'explanation', 'example', 'theorem',
        'proof', 'remark', 'exercise', 'solution', 'hint', 'layer_1', 'layer_2',
        'layer_3', 'layer_4', 'layer_5', 'attention', 'important',
    ];
?>
<div class="d-flex flex-column gap-1">
<? foreach ($accordion_variants as $variant) { ?>
    <? acc_single_item_start($variant, variant: $variant); ?>
    <p><code><?= $variant ?></code> maps to <code><?= variant_to_color($variant) ?></code>.</p>
    <? acc_single_item_end(); ?>
<? } ?>
</div>

<? ob_start(); acc_heading(4, 'Custom-color argument'); $custom_color_title = ob_get_clean(); ?>
<? acc_single_item_start($custom_color_title, custom_color: 'green', open: true, item_id: 'custom-green-item'); ?>
<p>The compatibility API also accepts the module's direct color names through <code>custom_color</code>.</p>
<? acc_single_item_end(); ?>

<h2>Collapse controls and buttons</h2>
<div class="d-flex gap-2">
<? foreach (['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'] as $variant) { ?>
    <? $collapse_id = collapse_button($variant, $variant); ?>
    <div id="<?= $collapse_id ?>" class="collapse"><p class="mb-0"><code><?= $variant ?></code> collapse panel</p></div>
<? } ?>
</div>

<p>
<? $link_collapse_id = collapse_a_start(); ?>Link trigger created by <code>collapse_a_start()</code><? collapse_a_end(); ?>
</p>
<div id="<?= $link_collapse_id ?>" class="collapse show">
    <div class="card card-body">This panel starts open. Both button and link triggers synchronize <code>aria-expanded</code>, support cancellable lifecycle events, and remain expanded when printed.</div>
</div>

<p class="d-flex gap-2">
    <button type="button" class="btn btn-primary">Primary button</button>
    <button type="button" class="btn btn-secondary">Secondary button</button>
    <button type="button" class="btn btn-danger">Danger button</button>
</p>

<h2>Core utilities, backgrounds, borders, and badges</h2>
<div class="d-flex gap-1">
<? foreach (['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'] as $variant) { ?>
    <span class="badge bg-<?= $variant ?>"><?= $variant ?></span>
    <span class="badge rounded-pill bg-<?= $variant ?>"><?= $variant ?> pill</span>
<? } ?>
<span class="badge"></span>
</div>

<div class="d-flex gap-2 mt-3">
    <div class="card border-primary"><div class="card-header bg-primary bg-opacity-50">50% primary</div><div class="card-body">Primary border</div></div>
    <div class="card border-secondary"><div class="card-header bg-secondary bg-opacity-100">Secondary</div><div class="card-body">Secondary border</div></div>
    <div class="card border-success"><div class="card-header bg-success">Success</div><div class="card-body">Success border</div></div>
    <div class="card border-danger"><div class="card-header bg-danger">Danger</div><div class="card-body">Danger border</div></div>
    <div class="card border-warning"><div class="card-header bg-warning">Warning</div><div class="card-body">Warning border</div></div>
    <div class="card border-info"><div class="card-header bg-info">Info</div><div class="card-body">Info border</div></div>
    <div class="card border-light"><div class="card-header bg-light">Light</div><div class="card-body">Light border</div></div>
    <div class="card border-dark"><div class="card-header bg-dark">Dark</div><div class="card-body">Dark border</div></div>
</div>

<div class="clearfix mt-3">
    <span class="float-start">float-start</span>
    <span class="float-end">float-end</span>
</div>
<p class="text-center">Centered text</p>
<p class="text-truncate" style="max-width: 16rem;">This deliberately long line demonstrates text truncation with an ellipsis.</p>
<div class="d-flex justify-content-center gap-1"><span>Centered flex</span><span>with gap</span></div>
<div class="d-flex flex-column">
<? for ($size = 1; $size <= 5; $size++) { ?>
    <div class="d-flex gap-<?= $size ?>"><span>gap-<?= $size ?></span><span>gap-<?= $size ?></span></div>
<? } ?>
</div>
<div class="d-none">This element demonstrates <code>d-none</code> and is intentionally invisible.</div>

<h3>Spacing scale</h3>
<? foreach (['mt', 'mb', 'pt', 'pb'] as $property) { ?>
    <div class="d-flex gap-1">
    <? for ($size = 0; $size <= 5; $size++) { ?>
        <span class="badge bg-primary <?= $property ?>-<?= $size ?>"><?= $property ?>-<?= $size ?></span>
    <? } ?>
    </div>
<? } ?>

<h2>Forms, validation, and alerts</h2>
<div class="alert alert-success"><p>Success alert</p></div>
<div class="alert alert-danger"><p>Danger alert</p></div>
<form>
    <p>
        <label class="form-label" for="demo-text">Text control</label>
        <input class="form-control" id="demo-text" name="text" value="Editable text">
    </p>
    <p>
        <label class="form-label" for="demo-textarea">Textarea control</label>
        <textarea class="form-control" id="demo-textarea" name="textarea">Editable multiline text</textarea>
    </p>
    <p>
        <label class="form-label" for="demo-select">Select control</label>
        <select class="form-select" id="demo-select" name="select"><option>First option</option><option>Second option</option></select>
    </p>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" id="demo-check" checked>
        <label class="form-check-label" for="demo-check">Checked checkbox</label>
    </div>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" id="demo-disabled" disabled>
        <label class="form-check-label" for="demo-disabled">Disabled checkbox</label>
    </div>
    <p class="input-group has-validation">
        <input class="form-control" value="Input group">
        <select class="form-select is-invalid" aria-describedby="group-feedback"><option>Invalid selection</option></select>
        <span id="group-feedback" class="invalid-feedback">Visible invalid feedback for the group.</span>
    </p>
    <p>
        <input class="form-control is-invalid" value="Invalid control" aria-describedby="control-feedback">
        <span id="control-feedback" class="invalid-feedback">Visible invalid feedback for the control.</span>
    </p>
</form>

<h2>Modal</h2>
<p class="d-flex gap-2">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#feature-modal">Open with a button</button>
    <a href="#feature-modal" data-bs-toggle="modal" data-bs-target="#feature-modal">Open with a link</a>
</p>
<div class="modal" id="feature-modal" tabindex="-1" aria-hidden="true" aria-labelledby="feature-modal-title">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="feature-modal-title">Modal behavior</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>The modal supplies a backdrop, body scroll lock, focus trapping and restoration, Escape/backdrop dismissal, and Bootstrap-compatible lifecycle events.</p>
                <form>
                    <label class="form-label" for="modal-value">Dismiss buttons reset modal forms</label>
                    <input class="form-control" id="modal-value" value="Change me, then press Close">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Focusable action</button>
            </div>
        </div>
    </div>
</div>

<h2>Tables</h2>
<div class="table-responsive">
    <table class="table table-striped table-striped-columns table-hover table-bordered">
        <caption class="caption-top">Striped rows and columns, hover, borders, top caption, contextual cells, active state, and group divider</caption>
        <thead><tr><th>Variant</th><th>Example</th></tr></thead>
        <tbody class="table-group-divider">
        <? foreach (['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'] as $variant) { ?>
            <tr class="table-<?= $variant ?>"><th><?= $variant ?></th><td class="<?= $variant === 'success' ? 'table-active' : '' ?>">Contextual row<?= $variant === 'success' ? ' with active cell' : '' ?></td></tr>
        <? } ?>
        </tbody>
    </table>
</div>

<table class="table table-sm table-borderless">
    <caption>Compact, borderless table with the default bottom caption</caption>
    <tbody><tr><th>table-sm</th><td>Reduced cell padding</td></tr><tr class="table-active"><th>table-active</th><td>Active row</td></tr></tbody>
</table>

<? foreach (['sm', 'md', 'lg', 'xl', 'xxl'] as $breakpoint) { ?>
<div class="table-responsive-<?= $breakpoint ?>">
    <table class="table"><caption><code>table-responsive-<?= $breakpoint ?></code></caption><tbody><tr><td>Responsive overflow wrapper at the <?= $breakpoint ?> breakpoint</td><td>Additional column</td><td>Additional column</td></tr></tbody></table>
</div>
<? } ?>

<? }; ?>
