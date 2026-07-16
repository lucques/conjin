<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');
        $c->activate_module('sql-js-knowledge-tagged');

        $c->activate_module('title');
        $c->run_macro('title', 'set', 'sql-js-knowledge-tagged');
    };
?>

<?
    $process = function(Target $target) {
        $gallery = new TaggedDb();

        sql_js_add_gallery_item($gallery, 'sunrise.svg', 'A warm sunrise illustration.', ['nature', 'warm']);
        sql_js_add_gallery_item($gallery, 'mountains.svg', 'A mountain landscape illustration.', ['nature', 'cool']);
        sql_js_add_gallery_item($gallery, 'night.svg', 'A quiet night-sky illustration.', ['night', 'cool']);
?>

<p>The module stores tagged entries in an in-browser sql.js database. Select a tag to filter the gallery, or use the controls to expand and collapse all entries.</p>

<?
        $db_var = sql_js_define_new_db_from_init_script_inline($gallery->generate_sql_init_script());
        sql_js_print_tagged_db($db_var);
    };
?>
