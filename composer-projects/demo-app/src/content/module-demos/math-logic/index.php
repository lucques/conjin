<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');

        $c->activate_module('math-logic');
    };
?>

<? $process = function (Target $target) { ?>


<p>
    Im Folgenden werden die Wahrheitstafeln für die logischen Operatoren $\neg$, $\land$, $\lor$, $\oplus$ und $\rightarrow$ gezeigt. Damit erhalten diese Symbole also ihre Semantik (= Bedeutung).
</p>
<?
    $not = [
        'op_un'   => '!',
        'operand' => 'p',
    ];

    $and = [
        'op_bin' => '&&',
        'left'   => 'p',
        'right'  => 'q',
    ];

    $or = [
        'op_bin' => '||',
        'left'   => 'p',
        'right'  => 's',
    ];

    $xor = [
        'op_bin' => 'xor',
        'left'   => 'p',
        'right'  => 'q',
    ];

    $implies = [
        'op_bin' => '->',
        'left'   => 'p',
        'right'  => 'q',
    ];
?>
<div style="display: grid; grid-template-rows: auto auto; grid-template-columns: auto auto auto auto auto; column-gap:40px; row-gap:10px;" class="text-center">
    <div>
        <dfn>Negation $\neg$</dfn>
    </div>
    <div>
        <dfn>Konjunktion $\land$</dfn>
    </div>
    <div>
        <dfn>Disjunktion $\lor$</dfn>
    </div>
    <div>
        <dfn>Antivalenz $\oplus$</dfn>
    </div>
    <div>
        <dfn>Implikation $\rightarrow$</dfn>
    </div>
    <div>
        <? logic_print_tt([$not]); ?>
    </div>
    <div>
        <? logic_print_tt([$and]); ?>
    </div>
    <div>
        <? logic_print_tt([$or]); ?>
    </div>
    <div>
        <? logic_print_tt([$xor]); ?>
    </div>
    <div>
        <? logic_print_tt([$implies]); ?>
    </div>
</div>
<p>
    Hier ein paar Beispiele, wie die Wahrheitstafeln gelesen werden.
</p>
<ul>
    <li>
        Für die Belegung $(p=1)$ hat die Formel $\neg p$ den Wahrheitswert $0$, ist also falsch/nicht erfüllt. 
    </li>
    <li>
        Für die Belegung $(p=1, q=0)$ hat die Formel $p \land q$ den Wahrheitswert $0$, ist also falsch/nicht erfüllt. 
    </li>
    <li>
        Für die Belegung $(p=1, q=1)$ hat die Formel $p \land q$ den Wahrheitswert $1$, ist also wahr/erfüllt.
    </li>
    <li>
        ...
    </li>
</ul>



<?
    $bsp_allg_formel_1_1 = [
        'op_bin' => '||',
        'left' => 'p',
        'right' => 'q',
    ];

    $bsp_allg_formel_1_2 = [
        'op_bin' => '->',
        'left' => 'p',
        'right' => 'q',
    ];

    $bsp_allg_formel_1 = [
        'op_bin' => '&&',
        'left' => $bsp_allg_formel_1_1,
        'right' => $bsp_allg_formel_1_2
    ];

    $bsp_allg_formel = [
        'op_un'  => '!',
        'operand' => $bsp_allg_formel_1
    ];

    $bsp_allg_formel_tex = logic_texify_exp($bsp_allg_formel);
    $bsp_allg_formel_1_tex = logic_texify_exp($bsp_allg_formel_1);
    $bsp_allg_formel_1_1_tex = logic_texify_exp($bsp_allg_formel_1_1);
    $bsp_allg_formel_1_2_tex = logic_texify_exp($bsp_allg_formel_1_2);
?>
<p>
    Haben wir die Wahrheitstafeln für die Operatoren, können wir die Wahrheitstafeln für beliebige Formeln aufstellen. Mit der folgenden Methode kann man sehr schnell die Wahrheitstafel aufstellen.
</p>
<div class="clearfix">
    <div class="float-end">
        <? logic_print_tt([$bsp_allg_formel, $bsp_allg_formel_1, $bsp_allg_formel_1_1, $bsp_allg_formel_1_2]); ?>
    </div>
    <p><strong>Methode 1: Zerlegung in Teilformeln</strong></p>
    <p>
        Wir <strong>zerlegen</strong> die zu untersuchende Formel in ihre Teilformeln und legen pro Teilformel eine Spalte an. Nun rechnen wir schrittweise zurück und <strong>kombinieren</strong> die Ergebnisse der Teilformeln, um die Ergebnisse der zusammengesetzten Formeln zu erhalten.
    </p>
    <p>
        Hier ein Beispiel für die Wahrheitstafel von $<?= $bsp_allg_formel_tex; ?>$. Diese Formel besteht aus den Teilformeln $<?= $bsp_allg_formel_1_tex ?>$ und $<?= $bsp_allg_formel_1_1_tex ?>$ und $<?= $bsp_allg_formel_1_2_tex ?>$. Fülle die Spalten von rechts nach links aus und verwende dabei die Ergebnisse wieder. So erhälst du zum Schluss die Wahrheitswerte der ursprünglichen Formel in der Spalte ganz links.
    </p>
</div>
<p><strong>Methode 2: Variablen ersetzen und vereinfachen</strong></p>
<div class="clearfix stack">
    <div class="float-end">
        \begin{align*}
        &~~ \neg((1 \lor 0) \land (1 \rightarrow 0))\\
        =&~~ \neg(1 \land 0)\\
        =&~~ \neg0\\
        =&~~ 1
        \end{align*}
    </div>
    <p>
        Eine andere Möglichkeit ist, zunächst alle Variablen durch die Wahrheitswerte gemäß Belegung zu ersetzen und den Term dann zu vereinfachen. Z.B. wird die Formel $<?= $bsp_allg_formel_tex; ?>$ für die Belegung $(p=1, q=0)$ wie rechts abgebildet vereinfacht.
    </p>
    <p>
        Anschließend kann die „1“ in der Wahrheitstafel in der zu $(p=1, q=0)$ gehörenden Zeile eingetragen werden.
    </p>
</div>



<p>
    Manche Formeln sind immer wahr und manche Formeln sind immer falsch. Sie werden dann Tautologie bzw. Kontradiktion genannt.
</p>

<div class="grid p" style="grid-template-columns: 1fr 1fr;">
    <div class="stack">
        <p>
            Eine Formel $\varphi$ ist eine <dfn>Tautologie</dfn>, wenn sie für alle Belegungen wahr ist.
        </p>
        <p>
            Man nennt $\varphi$ auch <dfn>allgemein gültig</dfn>.
        </p>
    </div>

    <div class="stack">
<?
    $bsp_tauto_1 = [
        'op_bin' => '||',
        'left'   => 'p',
        'right'  => [
            'op_un' => '!',
            'operand' => 'p'
        ]
    ];
    $bsp_tauto_2 = [
        'explicit_tex' => 'p \rightarrow (q \rightarrow p)',
        'op_bin' => '->',
        'left'   => 'p',
        'right'  => [
            'op_bin' => '->',
            'left'   => 'q',
            'right'  => 'p'
        ]
    ];
    $bsp_tauto_1_tex = logic_texify_exp($bsp_tauto_1);
    $bsp_tauto_2_tex = logic_texify_exp($bsp_tauto_2);
?>
            <p>Die Formeln $<?= $bsp_tauto_1_tex ?>$ und $<?= $bsp_tauto_2_tex ?>$ sind Tautologien.</p>
            <? logic_print_tt([$bsp_tauto_1, $bsp_tauto_2], printer: 'logic_texify_ep_exp_printer'); ?>
    </div>
</div>


<div class="grid p" style="grid-template-columns: 1fr 1fr;">
    <div class="stack">
        <p>
            Eine Formel $\varphi$ ist eine <dfn>Kontradiktion</dfn> (deutsch: <em>Widerspruch</em>), wenn sie für alle Belegungen falsch ist.
        </p>
        <p>
            Man nennt $\varphi$ auch <dfn>unerfüllbar</dfn>.
        </p>
    </div>

    <div class="stack">
<?
    $bsp_contra_1 = [
        'op_bin' => '&&',
        'left'   => 'p',
        'right'  => [
            'op_un' => '!',
            'operand' => 'p'
        ]
    ];
    $bsp_contra_2 = [
        'op_bin' => '&&',
        'left'   => 'p',
        'right'  => [
            'op_un' => '!',
            'operand' => [
                'op_bin' => '->',
                'left'   => 'q',
                'right'  => 'p'
            ]
        ]
    ];
    $bsp_contra_1_tex = logic_texify_exp($bsp_contra_1);
    $bsp_contra_2_tex = logic_texify_exp($bsp_contra_2);
?>
            <p>Die Formeln $<?= $bsp_contra_1_tex ?>$ und $<?= $bsp_contra_2_tex ?>$ sind Kontradiktionen.</p>
            <? logic_print_tt([$bsp_contra_1, $bsp_contra_2]); ?>
    </div>
</div>



<div class="grid p" style="grid-template-columns: 1fr 1fr;">
    <div class="stack">
        <p>
            Zwei Formeln $\varphi$ und $\psi$ sind <dfn>äquivalent</dfn> (deutsch: <em>gleichwertig</em>), wenn sie für jede Belegung zum <em>jeweils</em> <strong>gleichen Wahrheitswert</strong> auswerten.
        </p>
        <p>
            Man schreibt $\varphi ~\Leftrightarrow~ \psi$.
        </p>
        <p>
            Der Begriff der Äquivalenz ist fundamental, denn er bedeutet, dass Formeln <strong>austauschbar</strong> sind.
        </p>
        <ul>
            <li>
                <p>
                    Betrachte $p \rightarrow q$ und $\neg p \lor q$.
                </p>
            </li>
            <li>
                <p>
                    <em>Syntaktisch</em> sind die Formeln <strong>verschieden</strong>.<br>
                </p>
            </li>
            <li>
                <p>
                    <em>Semantisch</em> sind sie jedoch <strong>äquivalent</strong>.
                </p>
            </li>
        </ul>
    </div>

    <div class="stack">
<?
    $bsp_equi_1_1 = [
        'op_bin' => '->',
        'left'   => 'p',
        'right'  => 'q'
    ];
    $bsp_equi_1_2 = [
        'op_bin' => '||',
        'left'   => [
            'op_un' => '!',
            'operand' => 'p'
        ],
        'right'  => 'q'
    ];


    
    $bsp_equi_2_1 = [
        'op_bin' => '&&',
        'left'   => 'p',
        'right'  => [
            'op_bin' => '||',
            'left'   => 'q',
            'right'  => 'r'
        ]
    ];
    $bsp_equi_2_2 = [
        'explicit_tex' => '(p \land q) \lor (p \land r)',
        'op_bin' => '||',
        'left'   => [
            'op_bin' => '&&',
            'left'   => 'p',
            'right'  => 'q'
        ],
        'right'  => [
            'op_bin' => '&&',
            'left'   => 'p',
            'right'  => 'r'
        ]
    ];
    
    $bsp_equi_1_tex = logic_texify_exp($bsp_equi_1_1) . '~\Leftrightarrow~' . logic_texify_exp($bsp_equi_1_2);
    $bsp_equi_2_tex = logic_texify_exp($bsp_equi_2_1) . '~\Leftrightarrow~' . logic_texify_exp($bsp_equi_2_2);
?>
            <p>Bsp. 1: $<?= $bsp_equi_1_tex ?>$</p>
            <? logic_print_tt([$bsp_equi_1_1, $bsp_equi_1_2]); ?>
            <p>Bsp. 2: $<?= $bsp_equi_2_tex ?>$</p>
            <? logic_print_tt([$bsp_equi_2_1, $bsp_equi_2_2]); ?>
    </div>
</div>


<? }; ?>