<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');
        
        $c->activate_module('sql-js');
    };
?>

<? $process = function (Target $target) { ?>

<div id="results">

</div>

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
        document.getElementById('results').innerHTML = 'Success! Here is a row: ' + JSON.stringify(row);
    }
});
</script>

<? }; ?>