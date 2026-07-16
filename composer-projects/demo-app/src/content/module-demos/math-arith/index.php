<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');

        $c->activate_module('math-arith');
    };
?>

<? $process = function (Target $target) { ?>
    <? css_start(); ?>
#table-zusammengesetzte-terme td,
#table-zusammengesetzte-terme th {
    border-left: 1px solid black;
    border-right: 1px solid black;
}
<? css_end(); ?>

<p>
    Wir haben gesehen, dass Terme eigentlich nichts Neues sind. Es handelt sich im Grunde um „Rechenaufgaben“ wie z.B. $4 \cdot (3-7)$, nun jedoch mit einer wichtigen Erweiterung: Auch Variablen sind erlaubt. Aus diesem Grund ist z.B. auch $2 \cdot (x + 5)$ ein Term. Welche Rolle die Variablen <em>genau</em> übernehmen, schauen wir uns erst später an.  
</p>

<p>
    Wie sind Terme nun aber genau aufgebaut? Du musst dir Terme als eine Art <strong>Baukasten-System</strong> vorstellen. Es gibt zwei Typen von Termen:
</p>

<p>
    Ein <dfn>Grundterm</dfn> ist einer der kleinsten Terme, die es gibt: Eine einzelne Zahl, Größe (also eine Zahl mit Einheit) oder Variable.
</p>
<p>
    <em>Bsp.:</em> $\qquad 42\qquad 3~kg \qquad x$
</p>

<?
    $example_term_a = '42';
    $example_term_b = '3 \cdot 4';
?>

<div class="clearfix">
    <table id="table-zusammengesetzte-terme" class="table compact text-center float-end border border-dark rounded">
        <tr>
            <th>Fachbegriff</th>
            <th>Form</th>
            <th>Beispiel</th>
        </tr>
        <tr>
            <td>Summe</td>
            <td>$<?= math_make_empty_box(0) ?>  + <?= math_make_empty_box(1) ?>$</td>
            <td>$<?= math_make_box($example_term_a, 0) ?> + <?= math_make_box($example_term_b, 1) ?>$</td>
        </tr>
        <tr>
            <td>Differenz</td>
            <td>$<?= math_make_empty_box(0) ?> - <?= math_make_empty_box(1) ?>$</td>
            <td>$<?= math_make_box($example_term_a, 0) ?> - <?= math_make_box($example_term_b, 1) ?>$</td>
        </tr>
        <tr>
            <td>Produkt</td>
            <td>$<?= math_make_empty_box(0) ?> \cdot <?= math_make_empty_box(1) ?>$</td>
            <td>$<?= math_make_box($example_term_a, 0) ?> \cdot <?= math_make_box($example_term_b, 1) ?>$</td>
        </tr>
        <tr>
            <td style="border-bottom-width:0;">Quotient/Bruch</td>
            <td class="text-nowrap" style="border-bottom-width:0;">$<?= math_make_empty_box(0) ?> : <?= math_make_empty_box(1) ?>$</td>
            <td class="text-nowrap" style="border-bottom-width:0;">$<?= math_make_box($example_term_a, 0) ?> : <?= math_make_box($example_term_b, 1) ?>$</td>
        </tr>
        <tr>
            <td></td>
            <td class="text-nowrap">$\frac{<?= math_make_empty_box(0) ?>}{<?= math_make_empty_box(1) ?>}$</td>
            <td class="text-nowrap">$\frac{<?= math_make_box($example_term_a, 0) ?>}{<?= math_make_box($example_term_b, 1) ?>}$</td>
        </tr>
        <tr>
            <td>Entgegengesetzte Zahl</td>
            <td>$-~<?= math_make_empty_box(0) ?>$</td>
            <td>$-~<?= math_make_box($example_term_b, 0) ?>$</td>
        </tr>
        <tr>
            <td>Quadratzahl</td>
            <td>$<?= math_make_empty_box(0) ?>^2$</td>
            <td>$<?= math_make_box($example_term_b, 0) ?>^2$</td>
        </tr>
        <tr>
            <td>Quadratwurzel</td>
            <td>$\sqrt{<?= math_make_empty_box(0) ?>}$</td>
            <td>$\sqrt{<?= math_make_box($example_term_b, 0) ?>}$</td>
        </tr>
    </table>

    <p>
        Ein <dfn>zusammengesetzter Term</dfn> verbindet mehrere Terme mit einem Rechenzeichen.
    </p>
    <p>
        Du kannst dir also zwei Terme $<?= math_make_empty_box(0) ?>$ und $<?= math_make_empty_box(1) ?>$ schnappen und sie <dfn>zusammensetzen</dfn>, z.B. zu einer Summe $<?= math_make_empty_box(0) ?>  + <?= math_make_empty_box(1) ?>$.
    </p>
    <p>
        Man nennt $<?= math_make_empty_box(0) ?>$ und $<?= math_make_empty_box(1) ?>$ dann auch <dfn>Teilterme</dfn> von $<?= math_make_empty_box(0) ?>  + <?= math_make_empty_box(1) ?>$.
    </p>
    <p>
        Welche Teilterme $<?= math_make_empty_box(0) ?>$ und $<?= math_make_empty_box(1) ?>$ du auswählst, ist dabei egal: Es können
    </p>
    <ol>
        <li><strong>Grundterme</strong> sein (z.B. $<?= math_make_box($example_term_a, 0) ?>$), oder auch wiederum</li>
        <li><strong>zusammengesetzte Terme</strong> (z.B. $<?= math_make_box($example_term_b, 1) ?>$)</li>
    </ol>
    </p>
    <p>
        Manchmal musst du nur einen einzelnen Teilterm wählen, z.B. bei der Quadratwurzel: $\sqrt{<?= math_make_empty_box(0) ?>}$  
    </p>
</div>


<p>
    Du hast oben schon eine nützliche Darstellung von Termen gesehen: Mit Hilfe von Boxen wird eingezeichnet, wie sich die Terme entlang des oben erklärten Baukastenprinzips zusammensetzen.
</p>
<p>
    Die Boxen werden also so lange ineinander verschachtelt, bis man ganz im Inneren auf die Grundterme stößt. Grundterme enthalten dann keine weiteren Boxen mehr.
</p>


<p>
    Betrachte die folgenden Beispiele. Wenn du...
</p>
<ul>
    <li>von oben nach unten liest, dann siehst du, wie ein Term in Teilterme <strong>zerlegt</strong> wird.</li>
    <li>von unten nach oben liest, dann siehst du, wie ein Term aus Teiltermen <strong>konstruiert</strong> wird.</li>
</ul>

<?
    $term_example_1 = [
        'raw'    => '3 \cdot x + x^2',
        'type'   => 'Summe',
        'op_bin' => '+',
        'left'   => [
            'raw'    => '3 \cdot x',
            'type'   => 'Produkt',
            'op_bin' => '*',
            'left'   => '3',
            'right' => 'x'
        ],
        'right'  => [
            'raw'    => 'x^2',
            'type'   => 'Quadratzahl',
            'op_un' => 'sq',
            'operand'   => 'x',
        ],
    ];

    $term_example_2 = [
        'raw'    => '(\sqrt{4} + 4\cdot x) \cdot 7',
        'type'   => 'Produkt',
        'op_bin' => '*',
        'left'   => [
            'raw'    => '\sqrt{4} + 4\cdot x',
            'type'   => 'Summe',
            'op_bin' => '+',
            'left'   => [
                'raw'     => '\sqrt{4}',
                'type'   => 'Quadratwurzel',
                'op_un'   => 'sqrt',
                'operand' => '4'
            ],
            'right' => [
                'raw'     => '4\cdot x',
                'type'   => 'Produkt',
                'op_bin' => '*',
                'left'   => '4',
                'right'  => 'x'
            ]
        ],
        'right'  => 7
    ];
?>

<table class="table text-center border-dark">
    <tr>
        <th style="width: 20%;">Term (o. Boxen)</th>
        <th style="width: 25%;">Term (m. Boxen)</th>
        <th style="width: 20%;">Ist ein(e)...</th>
        <th>Teilterme</th>
    </tr>
<?
    aux_print_rows($term_example_1);
?>
</table>
<table class="table text-center border-dark">
    <tr>
        <th style="width: 20%;">Term (o. Boxen)</th>
        <th style="width: 25%;">Term (m. Boxen)</th>
        <th style="width: 20%;">Ist ein(e)...</th>
        <th>Teilterme</th>
    </tr>
<?
    aux_print_rows($term_example_2);
?>
</table>

<?
    $parens_example_1 = [
        'raw'    => '(2 + 3) \cdot 4',
        'op_bin' => '*',
        'left'   => [
            'op_bin' => '+',
            'left'   => '2',
            'right'  => '3'
        ],
        'right'  => '4'
    ];

    $parens_example_2 = [
        'raw'    => '2 + (3 \cdot 4)',
        'op_bin' => '+',
        'left'   => '2',
        'right'  =>  [
            'op_bin' => '*',
            'left'   => '3',
            'right'  => '4'
        ],
    ];
?>

<p>
    Wie du siehst, benötigt man bei der Box-Darstellung gar keine Klammern. Schließlich ist immer klar, wie die Terme verschachtelt sind. Es gilt nun eine ganz einfache Regel: <strong>Es wird immer von innen nach außen gerechnet.</strong>
    Mache dir noch einmal am folgenden Beispiel klar, dass die Anordnung und Verschachtelung der Boxen entscheidend ist.
</p>
<table class="table">
    <tr>
        <th>Term (m. Boxen)</th>
        <th>Bedeutung</th>
        <th>Term (o. Boxen)</th>
    </tr>
    <tr>
        <td>
            $<?= math_get_boxed_term($parens_example_1) ?> = 20$
        </td>
        <td>
            Addiere erst $2$ und $3$. Multipliziere anschließend mit $4$.
        </td>
        <td>
        $<?= $parens_example_1['raw']; ?> = 20$
        </td>
    </tr>
    <tr>
        <td>
            $<?= math_get_boxed_term($parens_example_2) ?> = 14$
        </td>
        <td>
            Multipliziere erst $3$ und $4$. Addiere anschließend zu $2$.
        </td>
        <td>
            $<?= $parens_example_2['raw']; ?> = 14$
        </td>
    </tr>
</table>

<p>
    Die Klammern sind also nur eine Kurzschreibweise für die Boxen. Die Boxen zu zeichnen, benötigt viel Zeit und Platz − die Klammern sind hingegen schnell notiert.
</p>


<? }; ?>