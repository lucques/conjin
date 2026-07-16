<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');

        $c->activate_module('sql-js-extensions');

        $c->activate_module('source', [
            'language'     => 'sql',
            'line_numbers' => false,
        ]);
    };
?>

<? $process = function(Target $target) { ?>

<? css_start(); ?>
    #content main {
        min-width:1000px;
    }
<? css_end(); ?>

<? $db_id_eisdiele = sql_js_define_new_db_from_init_script_inline_by_path(__DIR__ . '/res/eisdiele.sql'); ?>

<p>
    Betrachte die folgende Eisdielen-Datenbank, welche eine Tabelle mit Eisbehältern und eine Tabelle mit Eissorten enthält.
</p>
<div class="d-flex gap-3">
    <? sql_js_query_resultset_and_print($db_id_eisdiele, 'SELECT * FROM behaelter', title: '<strong><em>behaelter</em></strong>'); ?>
    <? sql_js_query_resultset_and_print($db_id_eisdiele, 'SELECT * FROM sorte', title: '<strong><em>sorte</em></strong>'); ?>
</div>

<h2>Relationenschemata</h2>

<p>
    Ein Relationenschema kann unabhängig von einer Datenbank dargestellt werden. Primärschlüssel werden unterstrichen, Fremdschlüssel kursiv gesetzt und ausgelassene Spalten durch Auslassungspunkte markiert.
</p>

<? sql_js_print_schema([
    [
        'name' => 'behaelter',
        'columns' => [
            ['type' => 'data', 'name' => 'name', 'isPartOfPrimaryKey' => true],
            ['type' => 'data', 'name' => 'preis'],
        ],
    ],
    [
        'name' => 'angebot',
        'columns' => [
            ['type' => 'data', 'name' => 'behaelter_name', 'isPartOfPrimaryKey' => true, 'isPartOfForeignKey' => true],
            ['type' => 'data', 'name' => 'sorte_name', 'isPartOfPrimaryKey' => true, 'isPartOfForeignKey' => true],
            ['type' => 'ellipsis'],
        ],
    ],
]); ?>

<p>
    Das Schema kann außerdem mit Abfrageergebnissen verbunden werden. Die Pfeile und die Tabellenköpfe blenden die Datensätze ein und aus. <em>behaelter</em> ist anfangs geöffnet; bei <em>sorte</em> zeigen die Auslassungspunkte an, dass das dargestellte Ergebnis gekürzt wurde.
</p>

<? sql_js_print_schema_with_queried_results(
    $db_id_eisdiele,
    [
        [
            'name' => 'behaelter',
            'columns' => [
                ['type' => 'data', 'name' => 'name', 'isPartOfPrimaryKey' => true],
                ['type' => 'data', 'name' => 'preis'],
            ],
            'sql' => 'SELECT name, preis FROM behaelter ORDER BY preis',
            'open' => true,
        ],
        [
            'name' => 'sorte',
            'columns' => [
                ['type' => 'data', 'name' => 'name', 'isPartOfPrimaryKey' => true],
                ['type' => 'data', 'name' => 'preis'],
            ],
            'sql' => 'SELECT name, preis FROM sorte ORDER BY preis LIMIT 2',
            'truncated' => true,
        ],
    ]
); ?>

<h2>SQL-Abfragen</h2>

<p>
    Indem mehrere Tabellen in der <code>FROM</code>-Klausel notiert werden, werden diese zeilenweise kombiniert: Es wird das sogenannte <dfn>kartesische Produkt</dfn> gebildet.
</p>

<?
    $sql = 'SELECT * FROM behaelter, sorte';
?>
<div class="clearfix">
    <div class="float-end">
        <? sql_js_query_resultset_and_print($db_id_eisdiele, $sql); ?>
    </div>
    <? source_start(); echo $sql; source_end(); ?>
</div>


<p>
    Manchmal erhalten durch die Bildung des kartesischen Produkts zwei Spalten denselben Namen. Wir können sie unterscheiden (Fachbegriff: „disambiguieren“), indem wir mit <code>ursprungstabelle.spalte</code> angeben, aus welcher Tabelle die Spalte stammt. Die folgende Abfrage liefert bspw. alle Kombinationen aus Behältern und Sorten, die in einer Waffel serviert werden.
</p>
<? ob_start(); ?>
SELECT behaelter.name,
       sorte.name
FROM   behaelter, sorte
WHERE  behaelter.name LIKE '%waffel'
<? $sql = ob_get_clean(); ?>
<div class="clearfix">
    <div class="float-end">
        <? sql_js_query_resultset_and_print($db_id_eisdiele, $sql); ?>
    </div>
    <? source_start(); echo $sql; source_end(); ?>
</div>
<p>
    Beachte, dass wir dann auch die Spalten noch umbenennen können. Die gleiche Anfrage, aber mit umbenannten Spalten, lautet wie folgt.
</p>
<? ob_start(); ?>
SELECT behaelter.name AS verpackung,
       sorte.name     AS kugel
FROM   behaelter, sorte
WHERE  verpackung LIKE '%waffel'
<? $sql = ob_get_clean(); ?>
<div class="clearfix">
    <div class="float-end">
        <? sql_js_query_resultset_and_print($db_id_eisdiele, $sql); ?>
    </div>
    <? source_start(); echo $sql; source_end(); ?>
</div>

<h2>Weitere Ergebnisdarstellungen</h2>

<p>
    Abfrageergebnisse können als gekürzt gekennzeichnet werden. Eine Abfrage ohne Datensätze erhält eine eigene Leermeldung.
</p>

<div class="d-flex gap-3 align-items-start">
    <? sql_js_query_resultset_and_print(
        $db_id_eisdiele,
        'SELECT name, preis FROM sorte ORDER BY preis DESC LIMIT 2',
        title: '<strong>Gekürztes Ergebnis</strong>',
        truncated: true
    ); ?>
    <? sql_js_query_resultset_and_print(
        $db_id_eisdiele,
        'SELECT name, preis FROM sorte WHERE preis < 0',
        title: '<strong>Leeres Ergebnis</strong>'
    ); ?>
</div>

<h2>Datenbankquellen</h2>

<p>
    Neben dem oben verwendeten serverseitigen SQL-Pfad kann eine Datenbank aus direkt übergebenem SQL, einer im Browser geladenen SQL-Datei oder einer binären SQLite-Datei initialisiert werden.
</p>

<div class="d-flex gap-3 align-items-start">
    <div>
        <h3>Direkt übergebenes SQL</h3>
        <?
            $db_inline = sql_js_define_new_db_from_init_script_inline(
                'CREATE TABLE quelle (art TEXT); INSERT INTO quelle VALUES (\'Inline-SQL\');'
            );
            sql_js_query_resultset_and_print($db_inline, 'SELECT art FROM quelle');
        ?>
    </div>
    <div>
        <h3>Geladene SQL-Datei</h3>
        <?
            $db_fetched_sql = sql_js_define_new_db_from_init_script_fetch(
                '/module-demos/sql-js-extensions/res/eisdiele.sql'
            );
            sql_js_query_resultset_and_print(
                $db_fetched_sql,
                'SELECT COUNT(*) AS anzahl FROM sorte',
                title: '<strong>Eissorten</strong>'
            );
        ?>
    </div>
    <div>
        <h3>Geladene SQLite-Datei</h3>
        <?
            $db_fetched_file = sql_js_define_new_db_from_db_file_fetch(
                '/module-demos/sql-js-extensions/res/eisdiele.sqlite'
            );
            sql_js_query_resultset_and_print(
                $db_fetched_file,
                'SELECT COUNT(*) AS anzahl FROM behaelter',
                title: '<strong>Behälter</strong>'
            );
        ?>
    </div>
</div>

<? }; ?>
