<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');
        $c->activate_module('bootstrap-extended');
        $c->add_subpage('variants');
    };
?>

<? $process = function (Target $target) { ?>

<? modal_start(); ?>
<? $demo_modal_id = modal_get_id(); ?>
<div class="modal-header">
    <h2 class="modal-title fs-5">Modal macro</h2>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body stack">
    <p><code>modal_start()</code> places the modal at the top of the document body through <code>doc-extensions</code>.</p>
    <p>The dialog is scrollable and uses Bootstrap's modal behavior.</p>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
</div>
<? modal_end(); ?>

<main class="container box-large">
    <h1>bootstrap-extended</h1>
    <p>This target activates <code>bootstrap-extended</code> directly with its standalone screen and print styles. It demonstrates the Conjin extensions and PHP macros; ordinary Bootstrap primitives appear only where an extension builds upon them.</p>

    <section>
        <h2>Stacks, slices, and boxes</h2>
        <div class="d-grid gap-3" style="grid-template-columns: repeat(3, 1fr);">
            <div class="box-small border rounded bg-light">
                <h3>Small box</h3>
                <p>Grid and flex children are stacks: their first and last slice margins are removed.</p>
            </div>
            <div class="box-medium border rounded bg-light">
                <h3>Medium box</h3>
                <p>The box utilities provide progressively larger padding while retaining stack behavior.</p>
            </div>
            <div class="box-large border rounded bg-light">
                <h3>Large box</h3>
                <p>This is the padding also used by the page's main container.</p>
            </div>
        </div>
        <div class="stack border rounded p-3 mt-3">
            <p class="p">A regular <code>.p</code> slice</p>
            <p class="pp">A <code>.pp</code> slice</p>
            <p class="ppp">A <code>.ppp</code> slice</p>
            <p class="pppp">A <code>.pppp</code> slice</p>
            <p class="ppppp">A <code>.ppppp</code> slice</p>
            <p class="my-0">An anti-slice with <code>.my-0</code></p>
        </div>
        <div class="clearfix mt-3">
            <span class="float-start badge bg-primary">float-start</span>
            <span class="float-end badge bg-success">float-end</span>
            The extended float spacing keeps surrounding text away from floated elements.
        </div>
    </section>

    <section>
        <h2>Extended colors</h2>
        <div class="d-flex gap-2">
            <span class="badge bg-orange">orange</span>
            <span class="badge bg-green">green</span>
            <span class="badge bg-blue">blue</span>
            <span class="badge text-bg-primary">primary</span>
            <span class="badge text-bg-success">success</span>
        </div>
        <table class="table table-bordered mt-3">
            <tbody>
                <tr class="table-orange"><th>table-orange</th><td>Extended table variant</td></tr>
                <tr class="table-green"><th>table-green</th><td>Extended table variant</td></tr>
                <tr class="table-blue"><th>table-blue</th><td>Extended table variant</td></tr>
            </tbody>
        </table>
    </section>

    <section>
        <h2>Accordions</h2>
        <? acc_start(only_one_open: true, id: 'bootstrap-extended-accordion'); ?>
        <? acc_item_start('Initially open item', variant: 'definition', open: true); ?>
        <p>The semantic <code>definition</code> variant maps to the extended orange color. This accordion permits only one open item.</p>
        <? acc_item_end(); ?>
        <? acc_item_start('Item with a header action', variant: 'example', header_actions: '<button type="button" class="accordion-header-action" data-bs-toggle="modal" data-bs-target="#' . $demo_modal_id . '" aria-label="Open modal">&#x2197;</button>'); ?>
        <p>The separate header action opens the modal while the title continues to control this item.</p>
        <? acc_item_end(); ?>
        <? acc_block_start('Permanent block', variant: 'solution', bg_as_collapsed: true); ?>
        <p>Accordion blocks share the themed layout but never collapse.</p>
        <? acc_block_end(); ?>
        <? acc_end(); ?>

        <? ob_start(); acc_heading(3, 'Single-item shorthand'); $single_item_title = ob_get_clean(); ?>
        <? acc_single_item_start($single_item_title, variant: 'attention'); ?>
        <p>The shorthand creates its own accordion container and supports heading-sized titles.</p>
        <? acc_single_item_end(); ?>

        <? acc_single_block_start('Single-block shorthand', variant: 'remark'); ?>
        <p>This shorthand creates a standalone permanent block.</p>
        <? acc_single_block_end(); ?>

        <? acc_header_only('Header-only shorthand', variant: 'theorem', bg_as_collapsed: true); ?>
    </section>

    <section>
        <h2>Standalone collapse controls</h2>
        <div class="d-flex gap-2 align-items-start">
            <div>
                <? $button_collapse_id = collapse_button('Button trigger', 'primary'); ?>
                <div id="<?= $button_collapse_id ?>" class="collapse">
                    <div class="card card-body mt-2">Content controlled by <code>collapse_button()</code>.</div>
                </div>
            </div>
            <div>
                <? $link_collapse_id = collapse_a_start(); ?>Link trigger<? collapse_a_end(); ?>
                <div id="<?= $link_collapse_id ?>" class="collapse">
                    <div class="card card-body mt-2">Content controlled by <code>collapse_a_start()</code> and <code>collapse_a_end()</code>.</div>
                </div>
            </div>
        </div>
    </section>

    <section>
        <h2>Tabs</h2>
        <? tabs_start(id: 'bootstrap-extended-tabs'); ?>
        <? tabs_item_start('First tab'); ?>
        <div class="box-medium"><p>The first buffered tab is active initially.</p></div>
        <? tabs_item_end(); ?>
        <? tabs_item_start('Second tab'); ?>
        <div class="box-medium"><p>Tab titles are escaped and each body retains rendered PHP and HTML.</p></div>
        <? tabs_item_end(); ?>
        <? tabs_item_start('Third tab'); ?>
        <div class="box-medium"><p>Bootstrap supplies the tab switching behavior.</p></div>
        <? tabs_item_end(); ?>
        <? tabs_end(); ?>
    </section>

    <section>
        <h2>Carousel</h2>
        <? carousel_start(with_controls: true, id: 'bootstrap-extended-carousel'); ?>
        <div class="box-large text-center bg-primary-subtle"><h3>First item</h3><p>The first carousel item is active automatically.</p></div>
        <? carousel_item(); ?>
        <div class="box-large text-center bg-success-subtle"><h3>Second item</h3><p><code>carousel_item()</code> starts each subsequent slide.</p></div>
        <? carousel_item(); ?>
        <div class="box-large text-center bg-warning-subtle"><h3>Third item</h3><p>The macro adds previous and next controls when requested.</p></div>
        <? carousel_end(); ?>
    </section>

    <section>
        <h2>Modal</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#<?= $demo_modal_id ?>">Open modal created by the macros</button>
    </section>

    <section>
        <h2>Custom list markers</h2>
        <?
            $ordered_list_classes = [
                'ol-decimal-circled', 'ol-lower-alpha-circled', 'ol-upper-alpha-circled',
                'ol-lower-alpha-bracket', 'ol-upper-alpha-bracket', 'ol-decimal-bracket',
                'ol-lower-roman-bracket', 'ol-lower-alpha-parens', 'ol-upper-alpha-parens',
                'ol-decimal-parens', 'ol-lower-roman-parens',
            ];
        ?>
        <div class="d-grid gap-3" style="grid-template-columns: repeat(3, 1fr);">
        <? foreach ($ordered_list_classes as $list_class) { ?>
            <div class="card card-body">
                <code><?= $list_class ?></code>
                <ol class="<?= $list_class ?> list-gap"><li>First item</li><li>Second item</li></ol>
            </div>
        <? } ?>
            <div class="card card-body">
                <code>ul-disc</code>
                <ul class="ul-disc list-gap"><li>First item</li><li>Second item</li></ul>
            </div>
            <div class="card card-body">
                <code>ul-square</code>
                <ul class="ul-square list-gap"><li>First item</li><li>Second item</li></ul>
            </div>
        </div>

        <ol class="list-group list-group-numbered bold-list-markers first-child-p mt-3">
            <li class="list-group-item"><p>Bold marker moved into the first paragraph.</p><p>Additional paragraph in the same item.</p></li>
            <li class="list-group-item"><p>The decoration composes with Bootstrap's numbered list group.</p></li>
        </ol>
    </section>

    <section>
        <h2>Code listing</h2>
        <pre><code>&lt;pre&gt;&lt;code&gt; receives an extended border automatically.&lt;/code&gt;&lt;/pre&gt;</code></pre>
        <p><dfn>Definition terms</dfn> receive the module's emphasized typography, and <span class="fs-7">fs-7 supplies the extra-small font size.</span></p>
    </section>
</main>

<? }; ?>
