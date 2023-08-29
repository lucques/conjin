<?
    /*
        This module offers some basic HTML generation.
    */

    function html_img(string $path,
                      string $alt = '',
                      ?int $width = null,
                      ?int $height = null,
                      string $id = null,
                      string $class = '',
                      string $style = ''): void {
        $html_id     = $id     !== null ? ' id="'     . $id     . '"' : '';
        $html_class  = $class  !== ''   ? ' class="'  . $class  . '"' : '';
        $html_style  = $style  !== ''   ? ' style="'  . $style  . '"' : '';
        $html_width  = $width  !== null ? ' width="'  . $width  . '"' : '';
        $html_height = $height !== null ? ' height="' . $height . '"' : '';
        echo '<img src="' . $path . '" alt="' . $alt . '"' . $html_id . $html_class . $html_style . $html_width . $html_height . '>';
    }
?>