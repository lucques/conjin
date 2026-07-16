<?
    class JavaWorldsPrinter {
        function __construct(public readonly string $jw_repo_path) {}


        /////////////////////
        // Print functions //
        /////////////////////

        function print_source(
            string $variant,
            string $program,
            string $file_name,
            bool $print_file_name = false,
            ?string $lang = null,
            ?bool $line_numbers = null,
            ?string $highlight_lines = null,
            ?string $id = null,
            $class = '',
            $style = ''
        ) {
            if ($print_file_name) {
?>
            <p>
                <strong><u><code class="language-none"><?= $file_name ?></code></u></strong>
            </p>
<?
            }

            source_file(
                $this->get_path_of_program_java_file($variant, $program, $file_name),
                lang: $lang,
                line_numbers: $line_numbers,
                highlight_lines: $highlight_lines,
                id: $id,
                class: $class,
                style: $style
            );
        }

        function print_download_link(
            string $variant,
            string $program,
        ) {
?>
            <p>
                <em>Projekt herunterladen:</em> <a href="<?= $this->get_url_of_program_zip($variant, $program) ?>"><code class="language-none"><?= $program ?>.zip</code></a>
            </p>
<?
        }


        /////////////////
        // URL getters //
        /////////////////

        function get_url_of_program_zip(
            string $variant,
            string $program
        ) {
            return to_url($this->jw_repo_path . '/variants/' . $variant . '/' . $program . '.zip');
        }


        function get_url_of_variant_uml_file(
            string $variant,
            string $file_name
        ) {
            return to_url($this->jw_repo_path . '/variants/' . $variant . '/uml/' . $file_name);
        }

        function get_url_of_program_uml_file(
            string $variant,
            string $program,
            string $file_name
        ) {
            return to_url($this->jw_repo_path . '/variants/' . $variant . '/programs/' . $program . '/uml/' . $file_name);
        }

        function get_url_of_program_static_file(
            string $variant,
            string $program,
            string $file_name
        ) {
            return to_url($this->jw_repo_path . '/variants/' . $variant . '/programs/' . $program . '/static/' . $file_name);
        }

        function get_path_of_program_java_file(
            string $variant,
            string $program,
            string $file_name
        ) {
            return $this->jw_repo_path . '/variants/' . $variant . '/programs/' . $program . '/java_src/' . $file_name;
        }
    }

?>
