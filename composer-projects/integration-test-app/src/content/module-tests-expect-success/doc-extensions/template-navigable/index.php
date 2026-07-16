<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-navigable');
        $c->activate_module('nav-build');
        $c->activate_module('doc-extensions');
    };
?>

<?
    $process = function (Target $target) {
        doc_extensions_add_head_element('<meta data-doc-extension-order="added-first" content="added-first">');
        doc_extensions_prepend_head_element('<meta data-doc-extension-order="prepended" content="prepended">');
        doc_extensions_add_head_element('<meta data-doc-extension-order="added-last" content="added-last">');

        doc_extensions_add_body_class('doc-extensions-body-class');

        body_top_element_start();
?>
<div data-doc-extension-top="first">First body-top extension</div>
<?
        body_top_element_end();

        body_top_element_start();
?>
<div data-doc-extension-top="second">Second body-top extension</div>
<?
        body_top_element_end();

        body_bottom_element_start();
?>
<div data-doc-extension-bottom="first">First body-bottom extension</div>
<?
        body_bottom_element_end();

        body_bottom_element_start();
?>
<div data-doc-extension-bottom="second">Second body-bottom extension</div>
<?
        body_bottom_element_end();

        css_start();
?>
#doc-extensions-content {
    color: rgb(12, 34, 56);
}
<?
        css_end();

        doc_extensions_add_js_after_dom_setup(
            "document.querySelector('#doc-extensions-content').dataset.domReady = 'yes'; window.docExtensionsExecutionOrder = ['first'];"
        );
        doc_extensions_add_js_after_dom_setup("window.docExtensionsExecutionOrder.push('second');");
?>

<p id="doc-extensions-content">Document extension content</p>

<?
    };
?>
