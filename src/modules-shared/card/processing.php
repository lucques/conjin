<?
    // TODO

    function rule_begin($number) {
?>
    <div class="row g-0">
        <div class="col-md-1 d-flex bg-secondary text-light align-items-center justify-content-center border-dark rounded-start border-start border-top border-bottom" style="text-align:center;">
            <span style="font-size: x-large;"><?= $number ?></span>
        </div>
        <div class="col-md-11 bg-primary bg-opacity-25 border-dark rounded-end border-top border-end border-bottom card-body " style="padding:10px;">
<?
    }

    function rule_end() {
?>
        </div>
    </div>
<?
    }
?>