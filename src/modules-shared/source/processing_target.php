<?
    $init_processing_target = function(Module $module, Target $target) {
        doc_extensions_add_body_class('language-' . $module->config->get('language'));
        doc_extensions_add_body_class(($module->config->get('line_numbers') ? '' : 'no-') . 'line-numbers');

        //////////////////
        // Global state //
        //////////////////

        $GLOBALS['source_last_macro'] = null; // Possible values: null, 'start'
    };


    ////////////
    // Macros //
    ////////////

    function source_start(?string $language = null, ?bool $line_numbers = null, ?string $id = null, $class = '', $style = '') {
        assert($GLOBALS['source_last_macro'] == null, 'Source listing already started');

        // Collect classes
        $classes = [$class];
        if ($language !== null) {
            $classes[] = 'language-' . $language;
        }
        if ($line_numbers !== null) {
            $classes[] = ($line_numbers ? '' : 'no-') . 'line-numbers';
        }
        $classes_imploded = implode(' ', $classes);

        $html_id     = $id                !== null ? ' id="'     . $id                . '"' : '';
        $html_class  = $classes_imploded  !== ''   ? ' class="'  . $classes_imploded  . '"' : '';
        $html_style  = $style             !== ''   ? ' style="'  . $style             . '"' : '';

        echo '<pre' . $html_id . $html_class . $html_style . '><code>';

        // Use output buffer to perform escaping
        ob_start();

        $GLOBALS['source_last_macro'] = 'start';
    }
    
    function source_end() {
        assert($GLOBALS['source_last_macro'] == 'start', 'Source listing did not start yet');

        // Escape and then output
        echo htmlspecialchars(ob_get_clean());
        echo '</code></pre>';

        $GLOBALS['source_last_macro'] = null;
    }

    function source_listing(string $source, ?string $language = null, ?bool $line_numbers = null, ?string $id = null, $class = '', $style = '') {
        source_start(language: $language, line_numbers: $line_numbers, id: $id, class: $class, style: $style);
        echo $source;
        source_end();
    }

    /**
     * Load path and print.
     */
    function source_file(string $path, ?string $language = null, ?bool $line_numbers = null, ?string $id = null, $class = '', $style = '') {
        source_listing(file_get_contents($path), $language, $line_numbers, $id, $class, $style);
    }

    /**
     * Given some `$first_line` and `$last_line` of a block within a source
     * file, the file is searched for the first occurrence of that block and
     * prints it, without indentation.
     * 
     * By "block" is meant: `$first_line` and `$last_line` have the same indentation.
     * So if you search for `$first_line='<p>'` and `$last_line='</p>'` in
     * ```
     * <html>
     *   <body>
     *     <p>
     *       First
     *     </p>
     *     <p>
     *       Second
     *     </p>
     *   </body>
     * </html>
     * ```
     * then this will just print the block without indentation, i.e.
     * ```
     * <p>
     *   First
     * </p>
     * ```
     * If you want to find the second occurrence, set `$skip=1` and it will print
     * ```
     * <p>
     *   Second
     * </p>
     * ```
     * 
     * Then indentation of the **arguments** `$first_line` and `$last_line` does
     * not matter as they will be `ltrim`med.
     */
    function source_file_block(string $path, string $first_line, string $last_line, int $skip = 0, ?string $language = null, ?bool $line_numbers = null, ?string $id = null, $class = '', $style = '') {
        $first_line = ltrim($first_line);
        $last_line = ltrim($last_line);
        
        // Read file
        $source = file_get_contents($path);

        $lines = preg_split("/\r\n|\n|\r/", $source);

        $number_start = null;
        $number_end = null;

        // Find `$first_line`
        for ($i = 0; $i < count($lines); $i++) {
            if (strpos($lines[$i], $first_line) !== false) {
                if ($skip == 0) {
                    $number_start = $i;
                    break;
                }
                else {
                    $skip--;
                }
            }
        }
        assert($number_start !== null, 'First line not found');

        // Measure indentation of `$first_line`
        preg_match('/[\s]*/', $lines[$number_start], $whitespace_matches);
        $indentation = $whitespace_matches[0];

        // Find `$last_line`
        for ($i = $number_start; $i < count($lines); $i++) {
            if (str_starts_with($lines[$i], $indentation . $last_line)) {
                $number_end = $i;
                break;
            }
        }
        assert($number_end !== null, 'Last line not found');

        // Print

        source_start(language: $language, line_numbers: $line_numbers, id: $id, class: $class, style: $style);
        // Trim indentation
        for ($i = $number_start; $i <= $number_end; $i++) {
            print(substr($lines[$i], strlen($indentation)) . "\n");
        }
        source_end();
    }

    function source_inline(string $source, ?string $language = null, ?string $id = null, $class = '', $style = '') {
        // Collect classes
        $classes = [$class];
        if ($language !== null) {
            $classes[] = 'language-' . $language;
        }
        $classes_imploded = implode(' ', $classes);

        $html_id     = $id                !== null ? ' id="'     . $id                . '"' : '';
        $html_class  = $classes_imploded  !== ''   ? ' class="'  . $classes_imploded  . '"' : '';
        $html_style  = $style             !== ''   ? ' style="'  . $style             . '"' : '';

        echo '<code' . $html_id . $html_class . $html_style . '>' . htmlspecialchars($source) . '</code>';
    }

    /**
     * Change the language and line numbering for defined scope
     */
    function source_scope_start(?string $language = null, ?bool $line_numbers = null) {
        $classes = [];
        if ($language !== null) {
            $classes[] = 'language-' . $language;
        }
        if ($line_numbers !== null) {
            $classes[] = ($line_numbers ? '' : 'no-') . 'line-numbers';
        }
        $classes_imploded = implode(' ', $classes);

        $html_class  = $classes_imploded  !== ''   ? ' class="'  . $classes_imploded  . '"' : '';

        echo '<div' . $html_class . '>';
    }

    function source_scope_end() {
        echo '</div>';
    }

?>