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

    function ref_img(string $path,
                     string $alt = '',
                     ?int $width = null,
                     ?int $height = null,
                     string $class = '',
                     string $style = ''): void
    {
        assert(file_exists($path), "File path for image `$path` does not exist");
        $path = realpath($path);
        
        // Read meta information
        $meta_file_path = ref_aux_change_file_extension($path, 'json');
        assert(file_exists($meta_file_path), "File path for meta information `$meta_file_path` does not exist");
        $meta_json = json_decode(load_file($meta_file_path), associative: true);

        // Add ref and get ref number
        $number = ref_add($meta_json);
?>
<div class="ref-img-container<?= $class != '' ? ' ' . $class : '' ?>">
    <? html_img(to_url($path), $alt, width: $width, height: $height, style: $style, id: 'ref-loc-' . $number); ?>
    <div class="ref-img-number"><a href="#ref-note-<?= $number ?>"><?= $number ?></a></div>
</div>
<?
    }

    function ref_add($params) {
        $GLOBALS['ref_notes'][] = $params;
        return count($GLOBALS['ref_notes']);
    }

    function ref_print_section() {
        if (count($GLOBALS['ref_notes']) == 0) {
            return;
        }
?>
<section>
<em>Quellen:</em>
<? ref_print_list(); ?>
</section>
<?
    }

    function ref_print_list() {
        if (count($GLOBALS['ref_notes']) == 0) {
            return;
        }
?>
<ol class="ref-notes">
<?
        foreach ($GLOBALS['ref_notes'] as $index => $ref) {
            $number = $index + 1;
            $license = isset($ref['license']) ? ',&nbsp;<a href="' . ref_aux_license_to_link($ref['license']) . '">' . $ref['license'] . '</a>' : '';
            $via = isset($ref['via']) ? ', via ' . $ref['via'] : '';
?>
    <li><a href="#ref-loc-<?= $number ?>" class="ref-note-backlink">↑</a> <a id="ref-note-<?= $number ?>" href="<?= $ref['url'] ?>"><?= $ref['author'] ?></a><?= $license . $via ?></li>
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

    function ref_aux_license_to_link(string $license) {
        if ($license == 'PD') {
            return 'https://en.wikipedia.org/wiki/Public_domain';
        }
        if ($license == 'CC0') {
            return 'https://creativecommons.org/publicdomain/zero/1.0/';
        }
        else if ($license == 'CC BY 4.0') {
            return 'https://creativecommons.org/licenses/by/4.0/';
        }
        else if ($license == 'CC BY-SA 4.0') {
            return 'https://creativecommons.org/licenses/by-sa/4.0/';
        }
        else if ($license == 'CC BY-NC 4.0') {
            return 'https://creativecommons.org/licenses/by-nc/4.0/';
        }
        else if ($license == 'CC BY-NC-SA 4.0') {
            return 'https://creativecommons.org/licenses/by-nc-sa/4.0/';
        }
        else if ($license == 'CC BY-ND 4.0') {
            return 'https://creativecommons.org/licenses/by-nd/4.0/';
        }
        else if ($license == 'CC BY-NC-ND 4.0') {
            return 'https://creativecommons.org/licenses/by-nc-nd/4.0/';
        }
        else {
            assert(false, "Unknown license `$license`");
        }
    }
?>