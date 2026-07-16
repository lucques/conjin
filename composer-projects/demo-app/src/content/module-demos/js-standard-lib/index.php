<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');
        $c->activate_module('js-standard-lib');

        $c->activate_module('title');
        $c->run_macro('title', 'set', 'js-standard-lib');
    };
?>

<? $process = function (Target $target) { ?>

<p>This module provides a few browser-global JavaScript helpers.</p>

<p>
    <button type="button" onclick="showNextId()">Generate ID</button>
    <output id="generated-id">No ID generated.</output>
</p>

<p>
    <button type="button" onclick="setDemoParameter()">Set URL parameter</button>
    <button type="button" onclick="removeDemoParameter()">Remove URL parameter</button>
    <output id="parameter-value">The <code>demo</code> parameter is not set.</output>
</p>

<p>
    <button type="button" onclick="assert(false, 'Demo assertion')">Log failed assertion</button>
    This writes to the browser console without throwing.
</p>

<script>
    function showNextId() {
        document.getElementById('generated-id').textContent = nextUniqueId();
    }

    function showDemoParameter() {
        const value = getURLParameter('demo');
        document.getElementById('parameter-value').textContent =
            value === null ? 'The demo parameter is not set.' : `The demo parameter is "${value}".`;
    }

    function setDemoParameter() {
        updateURLParameter('demo', 'example');
        showDemoParameter();
    }

    function removeDemoParameter() {
        updateURLParameter('demo', null);
        showDemoParameter();
    }

    showDemoParameter();
</script>

<? }; ?>
