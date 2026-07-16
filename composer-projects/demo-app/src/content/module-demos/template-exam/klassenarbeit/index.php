<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-exam', config: [
            'institution' => 'Demo-Schule',
            'class'       => 'Klasse 7C',
            'date_default' => '18.08.2024',
            'title'       => 'Klassenarbeit Nr. 1'
        ]);
        $c->activate_module('nav-build');
        $c->activate_module('mathjax-extensions');
        $c->activate_module('print-mode');
        $c->activate_module('grading');
    };
?>

<? $process = function (Target $target) { ?>


<p>
    <em>Alle Teilaufgaben sind unabhängig voneinander lösbar.</em>
</p>


<? exam_task_start([4], desc: '<strong>Berechne</strong>.', margin_top: 1); ?>
<ol style="display: grid; grid-template-columns: 1fr 1fr; row-gap:1.25cm;">
    <li>$(+14)+(-12) =$</li>
    <li>$\frac{1}{4} - 0{,}5 =$</li>
    <li>$-72 : (-9) =$</li>
    <li>$\frac{24}{5} \cdot (-\frac{3}{6}) =$</li>
</ol>
<? exam_task_end(); ?>



<? exam_task_start([8], desc: '<strong>Berechne</strong>. Schreibe den Rechenweg auf.', margin_top: 1); ?>
<ol style="display: grid; grid-template-columns: 1fr 1fr; row-gap:1.5cm;">
    <li>$-12 - (3-5) = $</li>
    <li>$1\frac{3}{5} \cdot 2-1{,}6 = $</li>
    <li>$6 + 6 \cdot 6 - 6 : 6 = $</li>
    <li>$-3{,}5 \cdot (2 - (-\frac{1}{2})) =$</li>
</ol>
<? exam_task_end(); ?>



<? exam_task_start([6], desc: '<strong>Berechne</strong>.', margin_top: 2); ?>
<ol style="display: grid; grid-template-columns: 1fr 1fr; gap:1.25cm;">
    <li>$2^8 =$</li>
    <li>$8^2 =$</li>
    <li>$\sqrt{81} = $</li>
    <li>$\sqrt{0{,}36} = $</li>
    <li>$(6-9)^2 = $</li>
    <li>$\sqrt{9} - \sqrt{16} =$</li>
</ol>
<? exam_task_end(); ?>




<? exam_task_start([4], desc: '<strong>Fülle</strong> die Lücken <strong>aus</strong>.', margin_top: 1); ?>
<ol style="display: grid; grid-template-columns: 1fr 1fr; row-gap:1.25cm;">
    <li>
        $-4 - \_\_\_\_\_\_ = 8$
    </li>
    <li>
        $-17 + \_\_\_\_\_\_ = -36$
    </li>
    <li>        
        $(-7{,}5) : \_\_\_\_\_\_ = -3$
    </li>
    <li>        
        $-9 - \_\_\_\_\_\_ : 4 = 0$
    </li>
</ol>
<? exam_task_end(); ?>



<? page_break_with_sign(margin_top: 1); ?>



<? exam_task_start([3], desc: '(Temperaturen)', margin_top: 0); ?>
<p>
    <em>Mit „Temperatur“ ist im Folgenden immer „durchschnittliche Tagestemperatur“ gemeint.</em>
</p>
<p>
    In Ahaus betrug am Montag die Temperatur 2℃. Am Dienstag und Mittwoch verdoppelte sie sich jeweils. Am Donnerstag nahm sie schlagartig um 10℃ ab, um dann am Freitag wieder um 1℃ zuzunehmen.
</p>
<p>
    <strong>Berechne</strong> die Temperatur, die am Freitag herrschte.
</p>
<? exam_task_end(); ?>



<? exam_task_start([3,4], desc: '(Dreiecke)', margin_top: 2); ?>
<ol style="display: grid; grid-template-columns: 1fr 1fr; row-gap:1cm; column-gap: 1cm;">
    <li class="stack">
        <p>
            <strong>Berechne</strong> die Größe des Winkels $\beta$, den Flächeninhalt $A$ und den Umfang $u$.<br>
            <em>Hinweis: Längen und Winkel in der Skizze sind nicht maßstabsgetreu.</em>
        </p>
        <p class="text-center"><img src="res/dreieck_skizze.png" alt="" style="max-width:60%;"></p>
    </li>
    <li class="stack">
        <p>
            <strong>Berechne</strong> den Flächeninhalt $A$ und den Umfang $u$ des folgenden Dreiecks.<br>
            <em>Hinweis: Das Dreieck kann auf dem Papier vermessen werden.</em>
        </p>
        <p class="text-center"><img src="res/dreieck_roh.png" alt="" style="max-width:80%;"></p>
    </li>
</ol>
<? exam_task_end(); ?>




<? exam_task_start([3,3], desc: 'Schafswiesen', margin_top: 2); ?>
<ol style="display: grid; grid-template-columns: 1fr 1fr; row-gap:1cm; column-gap: 1cm;">
    <li class="stack" style="grid-column-end: span 2;">
        <img src="res/wiese.png" alt="" class="float-end" style="width:5cm;">
        <p class="first-child">
            Ein Landwirt steckt die rechts abgebildete Schafswiese (dicke Linien) ab.
        </p>
        <ol>
            <li><strong>Berechne</strong>, wie viele Meter Zaun benötigt werden, um die Wiese zu umzäunen.</li>
            <li><strong>Berechne</strong>, wie viel Weidefläche den Schafen zur Verfügung steht.</li>
        </ol>
    </li>
    <li class="stack" style="grid-column-end: span 2;">
        <p>
            Ein anderer Landwirt will ebenfalls eine Wiese abstecken, mit dem Flächeninhalt $A = 27m^2$. Die längste Seite („Grundseite“) soll $9m$ lang sein. Die anderen beiden Seiten sollen gleich lang sein.
            <strong>Zeichne</strong> eine solche mögliche Wiese im Maßstab 1:100.
        </p>
    </li>
</ol>
<? exam_task_end(); ?>


<? exam_print_grading_table(GRADING_SEK_1_RAW, margin_top: 1); ?><? $GLOBALS['test_points']++ ?>



<? }; ?>
