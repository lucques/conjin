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

<div id="bootstrap-alternative-css-probe" class="d-none">Hidden by the module stylesheet.</div>

<div id="accordion-fixture">
<? acc_start(only_one_open: true, id: 'integration'); ?>
<? acc_item_start('First item', open: true, id: 'first'); ?>
<p>First accordion body</p>
<? acc_item_end(); ?>
<? acc_item_start('Second item', id: 'second'); ?>
<p>Second accordion body</p>
<? acc_item_end(); ?>
<? acc_end(); ?>
</div>

<div id="collapse-fixture">
<? $collapse_id = collapse_button('Toggle standalone collapse'); ?>
<div id="<?= $collapse_id ?>" class="collapse">
    <p>Standalone collapse body</p>
</div>
</div>

<button id="modal-trigger" type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-fixture">
    Open modal
</button>
<div class="modal" id="modal-fixture" tabindex="-1" aria-hidden="true" aria-labelledby="modal-fixture-title">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-fixture-title">Integration modal</h2>
            </div>
            <div class="modal-body">
                <form>
                    <label for="modal-fixture-input">Value</label>
                    <input id="modal-fixture-input" value="initial">
                </form>
            </div>
            <div class="modal-footer">
                <button id="modal-fixture-close" type="button" data-bs-dismiss="modal">Close</button>
                <button id="modal-fixture-last-action" type="button">Last action</button>
            </div>
        </div>
    </div>
</div>

<? }; ?>
