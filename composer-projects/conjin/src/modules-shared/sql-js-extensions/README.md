# sql-js-extensions

This module adds PHP helpers for creating and querying sql.js databases and JavaScript renderers for query results and compact database schemas.

## Dependencies

- `sql-js`: Provides the browser-side SQLite runtime and is activated automatically.
- `js-standard-lib`: Provides unique browser-side IDs for collapsible schema results and is activated automatically.

## Database helpers

- `sql_js_new_db_from_init_script_inline($sql)`: Returns a JavaScript expression of type `Promise<Database>` that initializes an in-memory database from the supplied SQL.
- `sql_js_new_db_from_init_script_fetch($url)`: Returns a JavaScript expression that fetches an SQL initialization script and resolves to the initialized database.
- `sql_js_new_db_from_db_file_fetch($url)`: Returns a JavaScript expression that fetches a SQLite database file and resolves to the loaded database.
- `sql_js_define_new_db_from_init_script_inline($sql)`, `sql_js_define_new_db_from_init_script_inline_by_path($path)`, `sql_js_define_new_db_from_init_script_fetch($url)`, and `sql_js_define_new_db_from_db_file_fetch($url)`: Print the required JavaScript binding and return its generated variable name.

All dynamic strings emitted into inline scripts are JSON encoded. Fetch helpers reject on HTTP error responses, and SQL.js initialization errors reject the returned promise.

## Query and rendering helpers

- `sql_js_query_resultset($db_var, $sql)`: Prints a promise binding that executes the SQL and returns its generated variable name.
- `sql_js_query_resultset_and_print($db_var, $sql, $title = '', $truncated = false)`: Executes the query and renders its result tables. The title accepts HTML; SQL result values and column names are HTML escaped.
- `sql_js_print_schema_with_queried_results($db_var, $tables, $show_button = true)`: Renders schema metadata and optional query results. Result rows can be collapsed when buttons are enabled.
- `sql_js_print_schema($tables)`: Renders schema metadata without querying a database.

The print helpers leave rejected promises unhandled so that SQL and loading failures remain observable as browser JavaScript errors. Call the lower-level promise helpers directly when an application needs custom error handling.

Each schema table has a `name` and a `columns` list. A data column uses `['type' => 'data', 'name' => 'column_name']` and may set `isPartOfPrimaryKey` or `isPartOfForeignKey`; an ellipsis column uses `['type' => 'ellipsis']`. A table may additionally define `sql`, `truncated`, and `open`.

## Configuration

- `import_from_url`: Optional base URL from which `res/library.js` and `res/styles.css` are loaded instead of using the module URL.
