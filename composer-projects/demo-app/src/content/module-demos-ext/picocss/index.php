<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');

        $c->activate_module('title');
        $c->run_macro('title', 'set', 'Pico CSS');

        $c->activate_module('picocss');
    };
?>

<? $process = function (Target $target) { ?>

<section class="container">
    <article>
        <header>A small Pico CSS card</header>

        <label for="picocss-name">Name</label>
        <input id="picocss-name" name="name" type="text" placeholder="Ada Lovelace">

        <label for="picocss-progress">Demo progress</label>
        <progress id="picocss-progress" value="65" max="100">65%</progress>

        <footer>
            <button type="button">Continue</button>
        </footer>
    </article>
</section>

<? }; ?>
