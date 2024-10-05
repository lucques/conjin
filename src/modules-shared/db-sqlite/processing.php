<?
    $init_processing_target = function (Module $module) {  
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            };

    
    ////////////
    // Macros //
    ////////////

    function connect_test(string $path) {
        // Create connection
        $GLOBALS['db_sqlite'] = new PDO('sqlite:' . $path);
    }

    function print_sql_query($query) {
        echo str_replace("`", "\"", $query);
    }

    function print_sql_result($db_name, $query, $table_name='', $css_class='') {
        $db = $GLOBALS['db'];
        $db->select_db($db_name);

        $result = $db->query($query);

        printf('<table class="db-table compact caption-top %s">', $css_class);
        echo '  <thead>';

        if ($table_name != '') {
            printf('  <tr class="db-table-name"><th colspan="%d">%s</th></tr>', $result->field_count, $table_name);
        }

        echo '      <tr>';
        while ($column = $result->fetch_field()) {
            printf("            <th>%s</th>", $column->name);
        }
        echo '      </tr>';
        echo '  </thead>';
        echo '  <tbody>';
        while ($row = $result->fetch_row()) {
            echo '      <tr>';
            foreach ($row as $column => $value) {
                printf("            <td>%s</td>", $value);
            }
            echo '      </tr>';
        }
        echo '  </tbody>';
        echo '</table>';
    }
?>