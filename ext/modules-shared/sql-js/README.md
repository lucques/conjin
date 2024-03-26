# Project info
- sql.js v1.8.0
- https://sql.js.org/
- License: MIT
- Downloaded the "wasm version, best runtime performance, smaller assets, requires configuration"


# Usage: JS

When activating this module, a JS file is added to the page and also the loading of WASM files is already configured. Example usage:

```html
<script>
    sqlJs.then(function(SQL) {
        // Create the database
        const db = new SQL.Database();
        // Run a query without reading the results
        db.run("CREATE TABLE test (col1, col2);");
        // Insert two rows: (1,111) and (2,222)
        db.run("INSERT INTO test VALUES (?,?), (?,?)", [1,111,2,222]);

        // Prepare a statement
        const stmt = db.prepare("SELECT * FROM test WHERE col1 BETWEEN $start AND $end");
        stmt.getAsObject({$start:1, $end:1}); // {col1:1, col2:111}

        // Bind new values
        stmt.bind({$start:1, $end:2});
        while(stmt.step()) { //
            const row = stmt.getAsObject();
            console.log('Here is a row: ' + JSON.stringify(row));
        }
    });
</script>
```


# Usage: TS + Webpack 5

In order to use this sql.js module in an app written in Typescript and bundled by webpack 5, do the following in your project.

1. Load the `sql.js` type annotations: `"@types/sql.js": "^1.4.9"`.

2. Now you can work with the `sql.js` package in your Typescript code.
    ```ts
    import initSqlJs from 'sql.js';
    declare const sqlJs: Promise<initSqlJs.SqlJsStatic>;

    const SQL = await sqlJs;
    const db = new SQL.Database();
    db.run("CREATE TABLE test (col1, col2);");
    ```

3. Activate this module.

4. Declare the `sql.js` package as external:
    ```js
    module.exports = {
        // ...
        externals: {
            'sql.js': {
                commonjs: 'sql.js',
                commonjs2: 'sql.js',
                amd: 'sql.js',
                root: 'initSqlJs',
            },
        },
    };
    ```
    This prevents webpack 5 from trying to include it in any bundle.

The reason for not including sql.js in a bundle is that I did not get it to work with the WASM dependencies etc. and struggled with the webpack configuration. This is a solution that works.