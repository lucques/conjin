<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');

        $c->activate_module('title');
        $c->run_macro(
            'title',
            'set',
            plain: 'title module demo',
            html: '<code>title</code> module demo',
            html_short: '<code>title</code> module',
            show_parents: 1
        );
    };
?>

<? $process = function (Target $target) { ?>

<p>
    The <code>title</code> module provides separate plain-text, HTML, and short titles for a target. This page uses <code>title module demo</code> as its plain title, <code>&lt;code&gt;title&lt;/code&gt; module demo</code> as its HTML title, and <code>&lt;code&gt;title&lt;/code&gt;</code> as the short title shown in the navigation.
</p>
<p>
    With <code>show_parents: 1</code>, the page heading also includes the title of its direct parent. Setting <code>is_part_of_content: false</code> in the <code>set</code> macro would suppress that automatically generated heading.
</p>

<h2>Title lookup functions</h2>

<?
    $format_titles = function (array $titles): string {
        return htmlspecialchars(
            json_encode($titles, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    };
?>

<table class="table">
    <thead>
        <tr>
            <th scope="col">Function</th>
            <th scope="col">Result for this page</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><code>get_top_level_plain_title()</code></td>
            <td><code><?= htmlspecialchars(get_top_level_plain_title(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></code></td>
        </tr>
        <tr>
            <td><code>get_top_level_html_title()</code></td>
            <td><code><?= htmlspecialchars(get_top_level_html_title(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></code></td>
        </tr>
        <tr>
            <td><code>get_plain_titles()</code></td>
            <td><code><?= $format_titles(get_plain_titles()) ?></code></td>
        </tr>
        <tr>
            <td><code>get_html_titles()</code></td>
            <td><code><?= $format_titles(get_html_titles()) ?></code></td>
        </tr>
        <tr>
            <td><code>get_plain_titles(false)</code></td>
            <td><code><?= $format_titles(get_plain_titles(false)) ?></code></td>
        </tr>
        <tr>
            <td><code>get_html_titles(false)</code></td>
            <td><code><?= $format_titles(get_html_titles(false)) ?></code></td>
        </tr>
    </tbody>
</table>

<p>
    By default, the list functions stop after the number of parents configured by <code>show_parents</code>. Passing <code>false</code> returns the complete path up to the top-level target.
</p>

<? }; ?>
