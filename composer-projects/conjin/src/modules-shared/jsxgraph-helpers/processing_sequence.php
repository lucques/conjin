<?
    namespace Conjin\ModulesShared\JSXGraphHelpers;

    use Conjin\ModulesShared\GraphingTypes\CustomElement;
    use Conjin\ModulesShared\GraphingTypes\ExplicitSequence;
    use Conjin\ModulesShared\GraphingTypes\RecursiveSequence;
    use Conjin\ModulesShared\GraphingTypes\SequencePlotOptions;
    use function Conjin\ModulesShared\GraphingTypes\translate_color_to_hex;


    ////////////////////
    // Print and draw //
    ////////////////////

    function plot_sequences(
        SequencePlotOptions $options,
        array $elements      // array of SequenceElement|ControlElement
    ) {
        $id = 'jsxgraph-plot-' . next_unique_id();
?>
        <div id="<?= $id ?>" class="jsxgraph-plot" style="width: <?= $options->width ?>px; height: <?= $options->height ?>px;"></div>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var board = JXG.JSXGraph.initBoard('<?= $id ?>', {
                    showCopyright: false,
                    boundingbox: [-1, <?= $options->y_max ?>, <?= $options->n_max ?>+1, <?= $options->y_min ?>],
                    axis: true,
                    grid: true,
                    showNavigation: false,
                    pan: {
                        enabled: false,
                        needTwoFingers: true,
                        needShift: false,
                        needMeta: false,
                        needCtrl: false,
                        needAlt: false,
                        needKey: false
                    },
                    zoom: {
                        enabled: false,
                        needTwoFingers: true,
                        needShift: false,
                        needMeta: false,
                        needCtrl: false,
                        needAlt: false,
                        needKey: false
                    },
                    defaultAxes: {
                        x : {
                            name: 'n',
                            withLabel: true,
                            label: { fontSize: 14, cssStyle: 'font-weight: 900;', position: 'last', offset: [0, 0], anchorX: 'middle', anchorY: 'top'},
                            ticks: {
                                scale: 1, // step size = 1
                                insertTicks: false,       // do not insert extra ticks
                                drawLabels: true,
                            }
                        },
                        y : {
                            withLabel:true,
                            name: 'y',
                            label: { fontSize: 14, cssStyle: 'font-weight: 900;', position: 'last', offset: [-8, 0], anchorX: 'right'},
                        }
                    }
                });
<?
                // Go through elements
                foreach ($elements as $name => $e) {

                    if (print_board_control_update_listener($e, $name)) {
                        continue;
                    }

                    if ($e instanceof ExplicitSequence) {

                        //////////////////////
                        // ExplicitSequence //
                        //////////////////////
?>
                {
                    const sequenceFunction = <?= $e->function_js ?>;
                    for (let n = 1; n <= <?= $options->n_max ?>; n++) {
                        const nCur = n;
                        board.create('point', [nCur, () => sequenceFunction(nCur)], {
                            name: '',
                            size: 3,
                            fixed: true,
                            strokeColor: '<?= translate_color_to_hex($e->color) ?>',
                            fillColor: '<?= translate_color_to_hex($e->color) ?>',
                            face: 'x'
                        });
                    }
                }
<?
                    }
                    elseif ($e instanceof RecursiveSequence) {

                        ////////////////////////
                        // RecursiveSequence //
                        ////////////////////////
?>
                {
                    const initialValue = () => <?= $e->init_val_js ?>;
                    const stepFunction = <?= $e->step_fun_js ?>;
                    const sequenceValue = (targetN) => {
                        let current = initialValue();
                        for (let n = 1; n < targetN; n++) {
                            current = stepFunction(current, n + 1);
                        }
                        return current;
                    };
                    for (let n = 1; n <= <?= $options->n_max ?>; n++) {
                        const nCur = n;
                        board.create('point', [nCur, () => sequenceValue(nCur)], {
                            name: '',
                            size: 3,
                            fixed: true,
                            strokeColor: '<?= translate_color_to_hex($e->color) ?>',
                            fillColor: '<?= translate_color_to_hex($e->color) ?>',
                            face: 'x'
                        });
                    }
                }
<?
                    }
                    elseif ($e instanceof CustomElement) {

                        ///////////////////
                        // CustomElement //
                        ///////////////////
                        echo $e->js_code . "\n";
                    }
                }
?>
            });
        </script>
<?
    }
?>
