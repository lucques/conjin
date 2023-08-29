<?
    /////////////////////
    // Definition/Rule //
    /////////////////////

    function rule_begin($number) {
?>
        <div class="row g-0">
            <div class="col-md-1 bg-secondary text-light rounded-start d-flex align-items-center justify-content-center border-start border-top border-bottom border-dark" style="text-align:center;">
                <span style="font-size: x-large;"><?= $number ?></span>
            </div>
            <div class="col-md-11 bg-primary bg-opacity-25 border-dark card-body rounded-end border border-dark">
<?
    }

    function rule_end() {
?>
            </div>
        </div>
<?
    }

    //////////
    // Math //
    //////////

    function plot_function($id, $f) {
?>

<?
    }

    //////////
    // Misc //
    //////////

    function empty_div($id) {
?>
        <div id="<?= $id ?>"></div>
<?
    }

    ///////////
    // Image //
    ///////////

    function insert_image($src, $float = 'none', $style = '', $caption='', $alt = '') {
        $class = '';
        if ($float == 'start') {
            $class = 'float-start';
        }
        elseif ($float == 'end') {
            $class = 'float-end';
        }
?>
        <figure<? if ($float != '') { echo ' class = "' . $class . '"'; } ?> style="<?= $style; ?>">
            <img src="<?= $src ?>" alt="<?= $alt ?>" style="max-width:100%;">
        </figure>
<?
    }
?>