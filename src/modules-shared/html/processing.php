<?
    function html_h(int $level, string $content, ?string $id = null, string $class = '', string $style = '') {
        $html_id    = $id    !== null ? ' id="'    . $id    . '"' : '';
        $html_class = $class !== ''   ? ' class="' . $class . '"' : '';
        $html_style = $style !== ''   ? ' style="' . $style . '"' : '';
        echo "<h$level$html_id$html_class$html_style>$content</h$level>";
    }

    function html_img(
        string $url,
        string $alt = '',
        ?int $width = null,
        ?int $height = null,
        ?string $id = null,
        string $class = '',
        string $style = '')
    {
        $html_id     = $id     !== null ? ' id="'     . $id     . '"' : '';
        $html_class  = $class  !== ''   ? ' class="'  . $class  . '"' : '';
        $html_style  = $style  !== ''   ? ' style="'  . $style  . '"' : '';
        $html_width  = $width  !== null ? ' width="'  . $width  . '"' : '';
        $html_height = $height !== null ? ' height="' . $height . '"' : '';
        echo '<img src="' . $url . '" alt="' . $alt . '"' . $html_id . $html_class . $html_style . $html_width . $html_height . '>';
    }

    // Before: `<p><$part_1> (expand)</p>`
    // After:  `<p><$part_1> (reduce)</p><p><?$part_2></p>`
    function html_p_truncated(string $part_1, string $part_2) {
        $id = next_unique_id();
        $html_id        = 'further-desc-' . $id;
        $html_id_expand = 'further-desc-expand-' . $id;
        $html_id_reduce = 'further-desc-reduce-' . $id;
?>
    <p class="mb-0">
        <?= $part_1 ?>
        <a data-bs-toggle="collapse" href="#<?= $html_id ?>" role="button" aria-expanded="false" aria-controls="<?= $html_id ?>" onclick="document.getElementById('<?= $html_id_expand ?>').classList.toggle('d-none'); document.getElementById('<?= $html_id_reduce ?>').classList.toggle('d-none');">
            <i class="bi bi-chevron-down" id="<?= $html_id_expand ?>"></i>
            <i class="bi bi-chevron-up d-none" id="<?= $html_id_reduce ?>"></i>
        </a>
    </p>
    <p class="collapse" id="<?= $html_id ?>">
        <?= $part_2 ?>
    </p>
<?
    }
?>