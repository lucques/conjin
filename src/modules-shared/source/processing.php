<?
    /*
        This module offers to include source code and to format it nicely.
        The formatting is done by Prism which is included already by default.
    */

    $init_processing = function(Module $module, Target $target) {
        doc_extensions_add_body_class('language-' . $module->config->get_or_default(['language'], 'none'));
        doc_extensions_add_body_class(($module->config->get_or_default(['line_numbers'], true) ? '' : 'no-') . 'line-numbers');
    };


    //////////////////////////
    // Used by CONTENT file //
    //////////////////////////

    function source_file(string $path, ?bool $line_numbers = null) {
        $source = htmlspecialchars(file_get_contents($path));

        if ($line_numbers === null) {
            echo '<pre><code>' . $source . '</code></pre>';
        }
        else {
            echo '<pre class="' . ($line_numbers ? '' : 'no-') . 'line-numbers"><code>' . $source . '</code></pre>';
        }
    }

    function source_file_block(string $path, string $first_line, ?bool $line_numbers = null) {
        $source = htmlspecialchars(file_get_contents($path));
        $lines = preg_split("/\r\n|\n|\r/", $source);

        $number_start = null;

        for ($i = 0; $i < count($lines); $i++) {
            if (strpos($lines[$i], $first_line) !== false) {
                $number_start = $i;
                break;
            }
        }

        assert($number_start !== null, 'Line not found');

        preg_match('/[\s]*/', $lines[$number_start], $whitespace_matches);
        $indentation = $whitespace_matches[0];

        if ($line_numbers === null) {
            echo '<pre><code>';
        }
        else {
            echo '<pre class="' . ($line_numbers ? '' : 'no-') . 'line-numbers"><code>';
        }

        // require as many lines as the indentation stays the same or higher.
        for ($i = $number_start; $i < count($lines); $i++) {
            if (str_starts_with($lines[$i], $indentation)) {
                print(substr($lines[$i], strlen($indentation)) . "\n");
            }
        }

        echo '</code></pre>';
    }

    function source_start(?bool $line_numbers = true, $class = '', $style = '') {
        if ($line_numbers === null) {
            echo '<pre' . ($class != '' ? ' class="' . $class . '"' : '') . '><code>';
        }
        else {
            echo '<pre class="' . ($line_numbers ? '' : 'no-') . 'line-numbers' . ($class != '' ? ' ' . $class : '') . '"><code>';
        }
    }
    
    function source_end() {
        echo '</code></pre>';
    }

    function source_inline(string $code, ?bool $line_numbers = true, $class = '', $style = '') {
        if ($line_numbers === null) {
            echo '<pre' . ($class != '' ? ' class="' . $class . '"' : '') . '><code>' . $code . '</code></pre>';
        }
        else {
            echo '<pre class="' . ($line_numbers ? '' : 'no-') . 'line-numbers' . ($class != '' ? ' ' . $class : '') . '"><code>' . $code . '</code></pre>';
        }
    }

    // Read source
    function source_read_start() {
        ob_start();
    }

    function source_read_end_get() {
        return ob_get_clean();
    }
?>