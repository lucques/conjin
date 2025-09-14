<?
    namespace Conjin\Modules\Nuggets;

use Conjin\Modules\Nuggets\Blog\BlogPostNugget;
use Conjin\Modules\Nuggets\Raw\RawNugget;

    ////////////////////////
    // Data types: Common //
    ////////////////////////

    interface Nugget {}

    readonly class CompositeNugget implements Nugget {
        public function __construct(
            public array $nuggets = [], // assoc array of CompositeNugget, LeafNugget
        ) {}
    }

    readonly abstract class LeafNugget implements Nugget {
        public function __construct(
            public ?array $config_default = null
        ) {}
    }


    ////////////////////////
    // Utils for printers //
    ////////////////////////

    readonly class PrinterConfig {
        public function __construct(
            public array $target_ids = [],  // Target that hosts the nugget pool
        ) {}
    }

    class Printer {
        function __construct(public PrinterConfig $config) { }

        static function make_default() {
            return new Printer(
                new PrinterConfig(
                    target_ids: ['nuggets'],
                )
            );
        }

        function print_raw(
            string $path,
            array $nugget_tree_path = [],
            ?array $config = null
        ) {
            //////////////////////////////
            // Retrieve defs and nugget // 
            //////////////////////////////

            $defs = load_defs_from_multi_nugget($this->config->target_ids, $path);
            assert(isset($defs['nugget']), 'No nugget defined in nugget file: ' . $this->get_dir_path($path) . '/nugget.php');
            $raw = load_nugget($defs['nugget'], $nugget_tree_path);
            assert($raw instanceof RawNugget);


            ////////////
            // Config //
            ////////////

            $config_final = merge_config($raw->config_default, $config);


            ///////////
            // Print //
            ///////////

            call_print_fun($defs['print_' . $raw->content_id], $config_final);
        }

        function print_blog_post_page(
            string $path,
            array $nugget_tree_path = [],

            string $lang = 'en',
   
            ?array $config = null,
        ) {

            //////////////////////////////
            // Retrieve defs and nugget // 
            //////////////////////////////

            $defs = load_defs_from_multi_nugget($this->config->target_ids, $path);
            assert(isset($defs['nugget']), 'No nugget defined in nugget file: ' . $this->get_dir_path($path) . '/nugget.php');
            $blog_post = load_nugget($defs['nugget'], $nugget_tree_path);
            assert($blog_post instanceof BlogPostNugget);


            ////////////
            // Config //
            ////////////

            $config_final = merge_config($blog_post->config_default, $config);


            ///////////
            // Print //
            ///////////

            html_h(2, $blog_post->title);

            if ($lang === 'en') {
?>
<p>
    <em><small>Published on <?= $blog_post->date->format('Y-m-d'); ?></small></em>
</p>
<?
            }
            else if ($lang === 'de') {
?>
<p>
    <em><small>Veröffentlicht am <?= $blog_post->date->format('d.m.Y'); ?></small></em>
</p>
<?
            }
            else {
                assert(false, 'Unsupported lang: ' . $lang);
            }

            call_print_fun($defs['print_' . $blog_post->content_id], $config_final);
        }

        function print_blog_post_list_item(
            string $path,
            array $nugget_tree_path = [],

            string $lang = 'en',
            string $note = '',
            bool $with_content = false,  // Whether to also print the content of the blog post (for preview)
   
            ?array $config = null,
        ) {

            //////////////////////////////
            // Retrieve defs and nugget // 
            //////////////////////////////

            $defs = load_defs_from_multi_nugget($this->config->target_ids, $path);
            assert(isset($defs['nugget']), 'No nugget defined in nugget file: ' . $this->get_dir_path($path) . '/nugget.php');
            $blog_post = load_nugget($defs['nugget'], $nugget_tree_path);
            assert($blog_post instanceof BlogPostNugget);


            ////////////
            // Config //
            ////////////

            $config_final = merge_config($blog_post->config_default, $config);


            ///////////
            // Print //
            ///////////
?>
<tr>
<?
            if ($lang === 'en') {
?>
    <td><p><?= $blog_post->date->format('Y-m-d'); ?></p></td>
<?
            }
            else if ($lang === 'de') {
?>
    <td><p><?= $blog_post->date->format('d.m.Y'); ?></p></td>
<?
            }
            else {
                assert(false, 'Unsupported lang: ' . $lang);
            }
?>
    <td>
        <p>
<?
            if ($with_content) {
?>
            <a href="<?= url_collect($blog_post->target_ids_canonical) ?>"><strong><?= $blog_post->title ?></strong></a>
<?
            }
            else {
?>
            <a href="<?= url_collect($blog_post->target_ids_canonical) ?>"><?= $blog_post->title ?></a>
<?
            }
?>
        <?php if ($note !== ''): ?>
            <small><?= $note ?></small>
        <?php endif; ?>
        </p>
        <?php if ($with_content): ?>
            <?php call_print_fun($defs['print_' . $blog_post->content_id], $config_final); ?>
        <?php endif; ?>
    </td>
</tr>
<?
        }

        function get_dir_path(string $path): string {
            return path_collect($this->config->target_ids, 'inc/' . $path);
        }
    }

    function load_defs_from_multi_nugget(array $target_ids, string $path) {
        $path = path_collect($target_ids, 'inc/' . $path) . '/nugget.php';
        return load_defs_from_script($path);
    }

    function load_nugget(Nugget $nugget, array $nugget_tree_path): Nugget {
        // Follow multi-nugget path
        $cur = $nugget;
        foreach ($nugget_tree_path as $path_component) {
            assert($cur instanceof CompositeNugget, 'Not a multi nugget');
            assert(isset($cur->nuggets[$path_component]), 'No such nugget in multi nugget: ' . $path_component);
            $cur = $cur->nuggets[$path_component];
        }

        return $cur;
    }

    function merge_config(?array $config_default, ?array $config): ?array {
        return $config_default == null
            ? null 
            : array_merge($config_default, $config ?? []);
    }

    function call_print_fun(callable $print_function, ?array $config) {
        if ($config === null) {
            $print_function();
        }
        else {
            $print_function(...$config);
        }
    }
?>