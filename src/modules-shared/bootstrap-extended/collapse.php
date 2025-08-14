<?
    $init_processing_target = function(Module $module, Target $target) {
        $GLOBALS['collapse_next_id'] = 0;
    };
    

    //////////
    // Core //
    //////////

    function collapse_button(string $title, string $variant = 'primary') {
        $id = 'collapse-' . $GLOBALS['collapse_next_id'];
        $GLOBALS['collapse_next_id']++;
?>
<button class="btn btn-<?= $variant ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $id ?>" aria-expanded="false" aria-controls="<?= $id ?>">
    <?= $title ?>
</button>
<?

        return $id;
    }

    function collapse_a_start() {
        $id = 'collapse-' . $GLOBALS['collapse_next_id'];
        $GLOBALS['collapse_next_id']++;
?>
<a data-bs-toggle="collapse" data-bs-target="#<?= $id ?>" aria-expanded="false" aria-controls="<?= $id ?>">
<?
        return $id;
    }

    function collapse_a_end() {
?>
</a>
<?
    }
?>