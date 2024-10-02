<?
    /*
        This module offers some helper functions for drawing function graphs etc.
    */
    
    $init_processing_target = function(Module $module, Target $target) {

        ///////////
        // State //
        ///////////

    };


    ////////////////////////
    // Exported Functions //
    ////////////////////////

    // $functions: list<record[name, attrs]>
    // $attrs:     record[x_min, x_max, y_min, y_max, width, height]
    function jsxgraph_plot_functions(array $functions, $attrs, array $lines = []) {
        $id = 'jsxgraph-plot-' . next_unique_id();
?>
        <div id="<?= $id ?>" class="jsxgraph-plot" style="width: <?= $attrs['width'] ?>px; height: <?= $attrs['height'] ?>px;"></div>
        <script>
            (function() {
                var board = JXG.JSXGraph.initBoard('<?= $id ?>', {
                    showCopyright: false,
                    boundingbox: [<?= $attrs['x_min'] ?>, <?= $attrs['y_max'] ?>, <?= $attrs['x_max'] ?>, <?= $attrs['y_min'] ?>],
                    axis: true,
                    grid: true,
                    showNavigation: false,
                    keepaspectratio: true,
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
                            name: 'x',
                            withLabel: true,
                            label: {
                                position: 'rt',
                                offset: [-10, -25]
                            },
                        },
                        y : {
                                withLabel:true,
                                name: 'y',
                                label: {
                                    position: 'rt',
                                    offset: [-25, -10]
                                }
                        }
                    }
                });
<?
                // Functions
                foreach ($functions as $f) {
?>
                board.create('functiongraph', [<?= $f['name']; ?>], <?= $f['attrs'] ?>);
<?
                }

                // Lines
                foreach ($lines as $line) {
?>
                board.create('line', [<?= $line['p1'] ?>, <?= $line['p2'] ?>], <?= $line['attrs'] ?>);
<?
                }
?>
            })();
        </script>
<?
    }

    function plot_function_card_begin($title, $function_bold = null, $function_background = null) {
?>
    <div class="card" style="width: 260px;">
        <div class="card-img-top d-flex justify-content-center" style="margin-top:5px;">
<?
            $functions = [];

            if ($function_bold !== null) {
                $functions[] = [
                    'name'  => $function_bold,
                    'attrs' => "{strokecolor:'black', strokewidth:2}"
                ];
            }

            if ($function_background !== null) {
                $functions[] = [
                    'name'  => $function_background,
                    'attrs' => "{strokecolor:'blue',  dash:2, strokewidth:3}"
                ];
            }

            jsxgraph_plot_functions(
                $functions, [
                    'x_min' => -5,
                    'x_max' => 5,
                    'y_min' => -5,
                    'y_max' => 5,
                    'width' => 300,
                    'height' => 300,
                ]);
?>
        </div>
        <div class="card-body">
            <p class="card-title"><strong><?= $title ?></strong></p>
<?
    }

    function plot_function_card_end() {
?>
        </div>
    </div>
<?
    }
?>