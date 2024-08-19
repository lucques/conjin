<?
    $init_processing_target = function(Module $module, Target $target) {
        $GLOBALS['footnotes_notes'] = [];
    };


    /////////////////////
    // Used by CONTENT //
    /////////////////////

    function footnote_start(): void
    {
        ob_start();
    }

    function footnote_end(): void
    {
        $text = ob_get_clean();

        // Add ref and get ref number
        $number = footnote_aux_add($text);
?>
<sup><a href="#footnote-note-<?= $number ?>" id="footnote-loc-<?= $number ?>"><?= $number ?></a></sup>
<?
    }


    //////////////////////
    // Used by TEMPLATE //
    //////////////////////

    function footnotes_any(): bool {
        return count($GLOBALS['footnotes_notes']) > 0;
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


    /////////////
    // Helpers //
    /////////////

    function footnote_aux_add($params): int {
        $GLOBALS['footnotes_notes'][] = $params;
        return count($GLOBALS['footnotes_notes']);
    }
?>