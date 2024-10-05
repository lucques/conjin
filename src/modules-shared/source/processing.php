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

    function source_start(
        ?string $language = null,
        ?bool $line_numbers = null,
        ?int $first_line_number = null,
        ?string $highlight_lines = null,
        ?string $id = null,
        $class = '',
        $style = ''
    ) {
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

        $html_id        = $id                !== null ? ' id="'         . $id                . '"' : '';
        $html_class     = $classes_imploded  !== ''   ? ' class="'      . $classes_imploded  . '"' : '';
        $html_style     = $style             !== ''   ? ' style="'      . $style             . '"' : '';
        $html_first_ln  = $first_line_number !== null ? ' data-start="' . $first_line_number . '"' : '';
        $html_highlight = $highlight_lines   !== null ? ' data-line="'  . $highlight_lines . '"' : '';

        echo '<pre' . $html_id . $html_class . $html_style . $html_first_ln . $html_highlight . '><code>';

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

    function source_listing(
        string $source,
        ?string $language = null,
        ?bool $line_numbers = null,
        ?int $first_line_number = null,
        ?string $highlight_lines = null,
        ?string $id = null,
        $class = '',
        $style = ''
    ) {
        source_start(language: $language, line_numbers: $line_numbers, first_line_number: $first_line_number, highlight_lines: $highlight_lines, id: $id, class: $class, style: $style);
        echo $source;
        source_end();
    }

    /**
     * Load path and print.
     */
    function source_file(
        string $path,
        ?string $language = null,
        ?bool $line_numbers = null,
        ?string $highlight_lines = null,
        ?string $id = null,
        $class = '',
        $style = ''
    ) {
        source_listing(file_get_contents($path), language: $language, line_numbers: $line_numbers, highlight_lines: $highlight_lines, id: $id, class: $class, style: $style);
    }

    /**
     * Given some `$first_line` and `$last_line` of a block within a source
     * file, the file is searched for the first occurrence of that block and
     * prints it. Indentation is stripped by default.
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
     * then this will just print the block without indentation by default, i.e.
     * ```
     * <p>
     *   First
     * </p>
     * ```
     * If you want to find the second occurrence, set `$skip_first_lines=1` and it will print
     * ```
     * <p>
     *   Second
     * </p>
     * ```
     * If you want to skip the ending of the block (therefore capturing more than
     * one block), set `$skip_block_closings=1` and it will print
     * ```
     * <p>
     *  First
     * </p>
     * <p>
     *  Second
     * </p>
     * 
     * Then indentation of the **arguments** `$first_line` and `$last_line` does
     * not matter as they will be `ltrim`med.
     * 
     * If you want to keep the indentation in the output, set
     * `$strip_indentation=false`.
     * 
     * If the file numbers should not be with respect to the file but the
     * printed output, set `$line_numbers_from_file=false`.
     */
    function source_file_block(
        string $path,
        string $block_opening_line,
        string $block_closing_line,
        int $skip_block_openings = 0,
        int $skip_block_closings = 0,
        int $skip_first_lines = 0,
        int $skip_last_lines = 0,
        bool $strip_indentation = true,
        bool $line_numbers_from_file = true,
        ?string $language = null,
        ?bool $line_numbers = null,
        ?string $highlight_lines = null,
        ?string $id = null,
        $class = '', $style = ''
    ) {
        $block_opening_line = ltrim($block_opening_line);
        $block_closing_line = ltrim($block_closing_line);
        
        // Read file
        $source = file_get_contents($path);

        $lines = preg_split("/\r\n|\n|\r/", $source);

        $number_start = null;
        $number_end = null;

        // Find `$first_line`
        for ($i = 0; $i < count($lines); $i++) {
            if (strpos($lines[$i], $block_opening_line) !== false) {
                if ($skip_block_openings == 0) {
                    $number_start = $i;
                    break;
                }
                else {
                    $skip_block_openings--;
                }
            }
        }
        assert($number_start !== null, 'First line not found');

        // Measure indentation of the block opening line
        preg_match('/[\s]*/', $lines[$number_start], $whitespace_matches);
        $indentation_block_opening_line = $whitespace_matches[0];

        // Skip first lines
        $number_start += $skip_first_lines;

        // Measure indentation of the first line that is actually printed
        preg_match('/[\s]*/', $lines[$number_start], $whitespace_matches);
        $indentation = $whitespace_matches[0];

        // Find `$last_line`
        for ($i = $number_start; $i < count($lines); $i++) {
            if (str_starts_with($lines[$i], $indentation_block_opening_line . $block_closing_line)) {
                if ($skip_block_closings == 0) {
                    $number_end = $i;
                    break;
                }
                else {
                    $skip_block_closings--;
                }
            }
        }
        assert($number_end !== null, 'Last line not found');

        // Skip last lines
        $number_end -= $skip_last_lines;

        // Line numbers
        $first_line_number = $line_numbers_from_file ? $number_start + 1 : 1;

        // Print
        source_start(language: $language, line_numbers: $line_numbers, first_line_number: $first_line_number, highlight_lines: $highlight_lines, id: $id, class: $class, style: $style);
        for ($i = $number_start; $i <= $number_end; $i++) {
            if ($strip_indentation) {
                print(substr($lines[$i], strlen($indentation)) . "\n");
            }
            else {
                print($lines[$i] . "\n");
            }
        }
        source_end();
    }

    function source_inline(
        string $source,
        ?string $language = null,
        ?string $id = null,
        $class = '',
        $style = ''
    ) {
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
    function source_scope_start(
        ?string $language = null,
        ?bool $line_numbers = null
    ) {
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