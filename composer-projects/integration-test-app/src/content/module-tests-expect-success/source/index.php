<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');
        $c->activate_module('source', [
            'language' => 'java',
            'line_numbers' => true
        ]);
    };
?>

<?
    $process = function (Target $target) {
?>

<div id="source-listing">
<?
        source_listing(
            '<section data-value="a&b">Literal HTML</section>',
            lang: 'html',
            line_numbers: false,
            first_line_number: 7,
            highlight_lines: '7',
            id: 'literal-listing',
            class: 'source-fixture',
            style: '--fixture-marker: 1;'
        );
?>
</div>

<div id="source-buffered">
<?
        source_start(lang: 'java', id: 'buffered-listing');
        echo "public class Buffered {\n    private int value = 1;\n    private String markup = \"<script>unsafe()</script>\";\n}";
        source_end();
?>
</div>

<div id="source-file">
<?
        source_file(__DIR__ . '/res/Example.java', lang: 'java', line_numbers: true, id: 'file-listing');
?>
</div>

<div id="source-file-block">
<?
        source_file_block(
            __DIR__ . '/res/Blocks.java',
            'void second() {',
            '}',
            lang: 'java',
            id: 'file-block'
        );
?>
</div>

<p id="source-inline">Before <? source_in('<strong>literal</strong> & text', lang: 'html', id: 'inline-code', class: 'inline-fixture'); ?> after</p>

<div id="source-scope">
<? source_scope_start(lang: 'css', line_numbers: false); ?>
<code id="scoped-code">a { color: red; }</code>
<? source_scope_end(); ?>
</div>

<?
    };
?>
