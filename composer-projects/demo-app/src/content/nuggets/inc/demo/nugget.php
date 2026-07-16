<?
    use Conjin\Modules\Nuggets\Exercise\ExerciseNugget;

    $nugget = ExerciseNugget::make_with_sol(
        name: 'Addition',
        points: [1],
    );

    $print_main = function() {
?>
<p>Calculate $2 + 2$.</p>
<?
    };

    $print_sol = function() {
?>
<p>$2 + 2 = 4$</p>
<?
    };
?>
