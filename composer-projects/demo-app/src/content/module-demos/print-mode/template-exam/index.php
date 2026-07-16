<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-exam', [
            'institution'  => 'Demo-Schule',
            'class'        => 'Mathematik-GK',
            'date_default' => '07.10.2025',
            'title'        => 'Test: Wiederholung'
        ]);
        $c->activate_module('nav-build');
        $c->activate_module('mathjax-extensions');
    };
?>

<?
    $process = function (Target $target) {
?>

<? exam_task_start([2, 3, 3, 3], title: 'Aufgabe 1', desc: 'Gleichungen lösen'); ?>
<p><strong>Berechne</strong> jeweils die Lösungsmenge $L$.</p>
<ol class="d-grid" style="grid-template-columns: 1fr 1fr; gap: 1em;">
    <li>$-3x-(x+3)=-5x+5{,}5$</li>
    <li>$7x=0{,}5x^2+24{,}5$</li>
    <li>$6x(2+3x)=0$</li>
    <li>$(-x)\cdot4x=2x$</li>
</ol>
<? exam_task_end(); ?>

<? exam_page_break(margin_top: 20); ?>

<? exam_task_start([2, 2, 3, 2], title: 'Aufgabe 2', desc: 'Der Impfstoff', margin_top: 0); ?>
<p>Ein empfindlicher Impfstoff wird kontrolliert aufgetaut. Seine Temperatur in °C wird nach $x$ Stunden durch die Funktion $f(x)=7{,}5x-70$ beschrieben.</p>
<ol>
    <li><strong>Erkläre</strong> die Bedeutung der beiden Zahlen in der Funktionsgleichung.</li>
    <li><strong>Berechne</strong> den Funktionswert $f(4)$.</li>
    <li><strong>Berechne</strong> die Nullstelle der Funktion.</li>
    <li><strong>Interpretiere</strong> die Nullstelle im Sachzusammenhang.</li>
</ol>
<? exam_task_end(); ?>

<?
    };
?>
