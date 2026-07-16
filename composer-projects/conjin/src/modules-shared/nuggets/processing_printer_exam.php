<?
    namespace Conjin\Modules\Nuggets;

    use Conjin\Modules\Nuggets\Exercise\ExerciseNugget;

    
    readonly class ExamPrinterConfig extends PrinterConfig {}


    class ExamPrinter extends Printer {
        function __construct(ExamPrinterConfig $config) {
            parent::__construct($config);
        }

        static function make_default() {
            return new ExamPrinter(
                new ExamPrinterConfig(
                    target_ids: ['nuggets'],
                )
            );
        }

        function print_exercise(
            string $path,
            array $nugget_tree_path = [],

            ?array $points = null,
            ?array $points_bonus = null,
            float $margin_top = 0.5,
            float $margin_bottom = 0,
            ?string $title = null,
            ?string $desc = null,
    
            ?array $config = null,
            string $class = '',
            string $style = ''
        ) {

            //////////////////////////////
            // Retrieve defs and nugget // 
            //////////////////////////////

            $defs = load_defs_from_multi_nugget($this->config->target_ids, $path);
            assert(isset($defs['nugget']), 'No nugget defined in nugget file: ' . $this->get_dir_path($path) . '/nugget.php');
            $exercise = load_nugget($defs['nugget'], $nugget_tree_path);
            assert($exercise instanceof ExerciseNugget);

            $points = $points ?? $exercise->main_item->points;
            assert($points !== null, 'Points must be defined either in config or in exercise main item');
            $points_bonus = $points_bonus ?? $exercise->main_item->points_bonus ?? [];
            assert(count($exercise->sub_parts) == 0, 'Sub parts not supported in exam printer');

            assert(count($exercise->main_item->solutions) <= 1, 'At most one solution supported in exam printer');


            ////////////
            // Config //
            ////////////

            $config_final = merge_config($exercise->config_default, $config);


            ////////////////
            // Print main //
            ////////////////

            exam_task_start(
                points: $points,
                points_bonus: $points_bonus,
                margin_top: $margin_top,
                margin_bottom: $margin_bottom,
                title: $title ?? '',
                desc: $desc ?? $exercise->main_item->name ?? '',
                class: $class,
                style: $style
            );
            call_print_fun($defs['print_' . $exercise->main_item->content_id], $config_final);
            exam_task_end();

            
            ///////////////
            // Print sol //
            ///////////////
            
            if (count($exercise->main_item->solutions) === 1) {
                exam_sol_start();
                call_print_fun($defs['print_' . $exercise->main_item->solutions[0]->content_id], $config_final);
                exam_sol_end();
            }
        }
    }
?>