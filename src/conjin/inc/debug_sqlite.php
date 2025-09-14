<?
    $file_name = $_GET['name'];
    $query = $_POST['query'] ?? '';
    $result = null;
    $error = null;

    if ($query !== '') {
        try {
            $db = new PDO('sqlite:' . path_store($file_name));
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->exec('PRAGMA foreign_keys = ON');
            $result = $db->query($query);
        }
        catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }

    echo '<html><head><title>SQLite editor: ' . htmlspecialchars($file_name) . '</title></head><body>';
    echo '<h1>SQLite editor: ' . htmlspecialchars($file_name) . '</h1>';
    echo 'Query:';
    echo '<form method="post" action="?what=store-file-sqlite&amp;name=' . urlencode($file_name) . '">';
    echo '<textarea name="query" rows="10" cols="100">' . htmlspecialchars($query) . '</textarea><br>';
    echo '<button type="submit">Execute</button>';
    echo '</form>';

    if ($error !== null) {
        echo '<h2>Error</h2><pre>' . htmlspecialchars($error) . '</pre>';
    }
    elseif ($result !== null && $result->columnCount() > 0) {
        $rows = $result->fetchAll(PDO::FETCH_ASSOC);
        $columns = count($rows) > 0 ? array_keys($rows[0]) : [];

        if (count($columns) == 0) {
            for ($i = 0; $i < $result->columnCount(); $i++) {
                $columns[] = $result->getColumnMeta($i)['name'];
            }
        }

        echo '<h2>Result</h2><table border="1"><tr>';
        foreach ($columns as $column) {
            echo '<th>' . htmlspecialchars($column) . '</th>';
        }
        echo '</tr>';

        foreach ($rows as $row) {
            echo '<tr>';
            foreach ($columns as $column) {
                echo '<td>' . htmlspecialchars((string) $row[$column], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td>';
            }
            echo '</tr>';
        }
        echo '</table>';
    }
    elseif ($result !== null) {
        echo '<h2>Result</h2><p>Query executed. Affected rows: ' . $result->rowCount() . '</p>';
    }

    echo '</body></html>';
?>
