<?
    ////////////
    // Macros //
    ////////////

    function hamster_source_file(string $path, ?string $language = null, ?bool $line_numbers = null, ?string $id = null, $class = '', $style = '') {
        $source = file_get_contents($path);

        // Omit the first comment: /*imperative program*/
        $source = substr($source, 22);

        source_listing(source: $source, language: $language, line_numbers: $line_numbers, id: $id, class: $class, style: $style);
    }

    function hamster_source($path, $line_numbers = true, $class = '', $style = '') {
        $source = file_get_contents($path);

        // Omit the first comment
        // /*imperative program*/
        $source = substr($source, 22);

        source_start($line_numbers, $class, $style);
        echo $source;
        source_end();
    }

    function hamster_before_after($src_before, $src_after) {
        ?>
            <div class="p text-center">
                <div class="d-grid" style="grid-template-columns: 1fr 1fr; column-gap:20px">
                    <div><img src="<?= $src_before ?>" alt="" style="max-width:100%;"></div>
                    <div><img src="<?= $src_after ?>" alt="" style="max-width:100%;"></div>
                    <div><em>Vorher</em></div>
                    <div><em>Nachher</em></div>
                </div>
            </div>
        <?
    }        
?>