<?
    $classlog = function() {
        $c = new Classlog();
?>


<? $c->long_start('2023-09-04', 'Erste Unterrichtsstunde'); ?>
<? $c->img('unterricht.png'); ?>
<ul>
    <li>Einstiegstest-7.pdf</li>
    <li>Einstiegstest-7-loesung.pdf</li>
    <li>Besprechung</li>
    <li><? $c->a_file('unterricht.png'); ?></li>
</ul>
<? $c->long_end(); ?>
<? $c->long_start('2023-09-11', 'Wiederholung Größen, Einheiten und Brüche'); ?>
<ul>
    <li>
        Brüche: Erweitern, Kürzen, Gleichnamig machen, Addieren, Subtrahieren, Multiplizieren
    </li>
</ul>
<? $c->long_end(); ?>
<? $c->long_start('2023-09-12', 'Brüche dividieren'); ?>
<? $c->daily_exercise(); ?>
<ul>
    <li>
        Terme aufstellen
    </li>
    <li>
        Brüche: Dividieren und Wiederholung
    </li>
</ul>
<? $c->long_end(); ?>
<? $c->add_short('2023-10-17', 'Nur ein Test eines short-logs'); ?>


<?
        return $c;
    }
?>