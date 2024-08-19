<?
    function html_h(int $level, string $content, ?string $id = null, string $class = '', string $style = ''): void {
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
        string $style = ''): void
    {
        $html_id     = $id     !== null ? ' id="'     . $id     . '"' : '';
        $html_class  = $class  !== ''   ? ' class="'  . $class  . '"' : '';
        $html_style  = $style  !== ''   ? ' style="'  . $style  . '"' : '';
        $html_width  = $width  !== null ? ' width="'  . $width  . '"' : '';
        $html_height = $height !== null ? ' height="' . $height . '"' : '';
        echo '<img src="' . $url . '" alt="' . $alt . '"' . $html_id . $html_class . $html_style . $html_width . $html_height . '>';
    }
?>