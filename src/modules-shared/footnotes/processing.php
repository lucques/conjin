<?
    /*
        This module allows to easily insert footnotes.
        The footnotes can then be printed e.g. at the end of the page.
    */

    $init_processing = function(Module $module, Target $target) {
        $GLOBALS['footnotes_notes'] = [];
    };


    ////////////
    // Images //
    ////////////

    function footnotes_start(): void
    {
        ob_start();
    }

    function footnotes_end(): void
    {
        $text = ob_get_clean();

        // Add ref and get ref number
        $number = footnotes_add($text);


?>
<sup><a href="#footnote-note-<?= $number ?>" id="footnote-loc-<?= $number ?>"><?= $number ?></a></sup>
<?
    }

    function footnotes_add($params) {
        $GLOBALS['footnotes_notes'][] = $params;
        return count($GLOBALS['footnotes_notes']);
    }

    function footnotes_print_section() {
        if (count($GLOBALS['footnotes_notes']) == 0) {
            return;
        }
?>
<section>
<? footnotes_print_list(); ?>
</section>
<?
    }

    function footnotes_print_list() {
        if (count($GLOBALS['footnotes_notes']) == 0) {
            return;
        }
?>
<ol class="footnotes-notes">
<?
        foreach ($GLOBALS['footnotes_notes'] as $index => $footnote) {
            $number = $index + 1;
?>
    <li>
        <a href="#footnote-loc-<?= $number ?>" class="footnote-note-backlink">↑</a> <span id="footnote-note-<?= $number ?>"><?= $footnote ?></span>
    </li>
<?
        }
?>
</ol>
<?
    }
?>