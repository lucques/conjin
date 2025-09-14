<?
    namespace Conjin\Modules\Nuggets;

    use Conjin\Modules\Nuggets\Exercise\ExerciseExplanation;
    use Conjin\Modules\Nuggets\Exercise\ExerciseHint;
    use Conjin\Modules\Nuggets\Exercise\ExerciseItem;
    use Conjin\Modules\Nuggets\Exercise\ExerciseNugget;
    use Conjin\Modules\Nuggets\Exercise\ExerciseSol;
    use Conjin\Modules\Nuggets\Raw\RawNugget;


    readonly class BootstrapPrinterConfig extends PrinterConfig {}

    class BootstrapPrinter extends Printer {
        private int $cur_ex_number;

        function __construct(BootstrapPrinterConfig $config, $ex_number_start = 1) {
            parent::__construct($config);
            $this->cur_ex_number = $ex_number_start;
        }

        static function make_default($ex_number_start = 1) {
            return new BootstrapPrinter(
                new BootstrapPrinterConfig(
                    target_ids: ['nuggets'],
                ),
                $ex_number_start
            );
        }

        function print_exercise(
            string $path,
            array $nugget_tree_path = [],
            ?array $config = null,
            bool $numbered = true,
            bool $sub_items_numbered = true,
            bool $show_source = false,
            bool $show_source_detail = false,
            bool $open_main = false,
            array $open_content_ids = [],  // content_id's to open
            bool $open_all = false,
            bool $hide_solutions = false,
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


            ////////////
            // Config //
            ////////////

            $config_final = merge_config($exercise->config_default, $config);


            ///////////
            // Print //
            ///////////

            // Add `exercise` class
            $class .= ($class == '' ? '' : ' ') . 'exercise';

            acc_start(class: $class, style: $style);

            // Print main item
            
            // Title
            $title = $numbered
                ? '<strong>Aufgabe ' . $this->cur_ex_number . '</strong>'
                : '<strong>Aufgabe</strong>';
            if ($exercise->main_item->name !== null) {
                $title .= ': ' . $exercise->main_item->name;
            }

            if ($show_source && $exercise->source !== null) {
                $title .= ' <em>(' . $exercise->source;
                if ($exercise->source_detail !== null) {
                    $title .= ', ' . $exercise->source_detail;
                }
                $title .= ')</em>';
            }
            else if ($show_source_detail && $exercise->source_detail !== null) {
                $title .= ' <em>(' . $exercise->source_detail . ')</em>';
            }

            $open = $open_all || $open_main || in_array($exercise->main_item->content_id, $open_content_ids);

            acc_item_start($title, variant: 'exercise', open: $open);
            call_print_fun($defs['print_' . $exercise->main_item->content_id], $config_final);
            acc_item_end();

            // Print hints and sols
            foreach ($exercise->main_item->hints as $hint) {
                $open = $open_all || in_array($hint->content_id, $open_content_ids);
                $this->print_exercise_hint($defs, $config_final, $hint, $open);
            }
            foreach ($exercise->main_item->solutions as $solution) {
                $open = $open_all || in_array($solution->content_id, $open_content_ids);

                if (!$hide_solutions) {
                    $this->print_exercise_solution($defs, $config_final, $solution, $open);
                }
            }

            $item_number = 1;
            foreach ($exercise->sub_parts as $sub_part) {
                $open = $open_all || in_array($sub_part->content_id, $open_content_ids);

                if ($sub_part instanceof ExerciseItem) {
                    $title = $sub_items_numbered
                        ? '<strong>' . $this->cur_ex_number . '.' . ($item_number++) . '</strong>'
                        : '<strong>Teilaufgabe</strong>';

                    if ($sub_part->name !== null) {
                        $title .= ': ' . $sub_part->name;
                    }

                    acc_item_start($title, variant: 'exercise', open: $open);
                    call_print_fun($defs['print_' . $sub_part->content_id], $config_final);
                    acc_item_end();

                    // Print hints and sols
                    foreach ($sub_part->hints as $hint) {
                        $open = $open_all || in_array($hint->content_id, $open_content_ids);
                        $this->print_exercise_hint($defs, $config_final, $hint, $open);
                    }
                    foreach ($sub_part->solutions as $solution) {
                        $open = $open_all || in_array($solution->content_id, $open_content_ids);

                        if (!$hide_solutions) {
                            $this->print_exercise_solution($defs, $config_final, $solution, $open);
                        }
                    }
                }
                elseif ($sub_part instanceof ExerciseExplanation) {
                    $title = $sub_part->title ?? '<strong>Umbenannt</strong>';

                    acc_item_start($title, variant: 'explanation', open: $open);
                    call_print_fun($defs['print_' . $sub_part->content_id], $config_final);
                    acc_item_end();
                }
                else {
                    assert(false, 'Unknown part type');
                }
            }

            acc_end();

            $this->cur_ex_number++;
        }


        /////////////
        // Private //
        /////////////

        private function print_exercise_hint($defs, $config, ExerciseHint $hint, bool $open) {
            $title = $hint->title ?? '<strong>Tipp</strong>';

            acc_item_start($title, variant: 'hint', open: $open);
            call_print_fun($defs['print_' . $hint->content_id], $config);
            acc_item_end();
        }

        private function print_exercise_solution($defs, $config, ExerciseSol $solution, bool $open) {
            $title = $solution->title ?? '<strong>Lösungsvorschlag</strong>';

            acc_item_start($title, variant: 'solution', open: $open);
            call_print_fun($defs['print_' . $solution->content_id], $config);
            acc_item_end();
        }
    }
?>