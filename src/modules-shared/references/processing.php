<?
    /*
        This module allows to easily tag content with their references.
        The references can then be listed e.g. at the end of the page.
    */

    $init_processing = function(Module $module, Target $target) {
        $GLOBALS['ref_notes'] = [];
    };


    ////////////
    // Images //
    ////////////

    function ref_img(array $target_ids,
                     string $relative_path,
                     string $alt = '',
                     ?int $width = null,
                     ?int $height = null,
                     string $class = '',
                     string $style = ''): void
    {
        $img_file_path = path_collect($target_ids) . '/' . $relative_path;
        assert(file_exists($img_file_path), "File path for image `$img_file_path` does not exist");
        $img_file_path = realpath($img_file_path);
        
        // Read meta information
        $meta_file_path = ref_aux_change_file_extension($img_file_path, 'json');
        assert(file_exists($meta_file_path), "File path for meta information `$meta_file_path` does not exist");
        $meta_json = json_decode(load_file($meta_file_path), associative: true);

        // Add ref and get ref number
        $number = ref_add($meta_json);
?>
<div class="ref-img-container">
    <? html_img(path_to_url($img_file_path), $alt, width: $width, height: $height, class: $class, style: $style, id: 'ref-loc-' . $number); ?>
    <div class="ref-img-number"><a href="#ref-note-<?= $number ?>"><?= $number ?></a></div>
</div>
<?
    }

    function ref_add($params) {
        $GLOBALS['ref_notes'][] = $params;
        return count($GLOBALS['ref_notes']);
    }

    function ref_print_list() {
?>
<ol class="ref-notes">
<?
        foreach ($GLOBALS['ref_notes'] as $index => $ref) {
            $number = $index + 1;
?>
    <li><a href="#ref-loc-<?= $number ?>" class="ref-note-backlink">↑</a> <a id="ref-note-<?= $number ?>" href="<?= $ref['url'] ?>"><?= $ref['author'] ?></a></li>
<?
        }
?>
</ol>
<?
    }

    function ref_aux_change_file_extension(string $path, string $new_extension) {
        $path_info = pathinfo($path);
        return $path_info['dirname'] . '/' . $path_info['filename'] . '.' . $new_extension;
    }
?>