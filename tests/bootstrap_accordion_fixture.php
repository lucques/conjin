<?
    $module = $argv[1] ?? '';
    if (!in_array($module, ['bootstrap-extended', 'bootstrap-alternative'], true)) {
        throw new InvalidArgumentException('Expected an accordion module name');
    }

    $framework = dirname(__DIR__) . '/composer-projects/conjin/src';
    require $framework . '/conjin/inc/core_pure.php';
    require $framework . '/modules-shared/' . $module . '/processing.php';
    require $framework . '/modules-shared/' . $module . '/acc.php';
    $GLOBALS['acc_stack'] = [];
    $GLOBALS['acc_next_id_numeric'] = 0;

    acc_start(id: 'fixture-set', only_one_open: true);
    acc_item_start('Legacy item');
    echo '<p>Legacy body</p>';
    acc_item_end();

    acc_item_start('Hidden item', hidden: true, with_anchor: true);
    echo '<p>Hidden body</p>';
    acc_item_end();

    acc_item_start('Detail <em>item</em>', with_anchor: true);
    echo '<p>Outer body</p>';
    acc_start(id: 'nested-set');
    acc_item_start('Nested item', with_anchor: true);
    echo '<p>Nested body</p>';
    acc_item_end();
    acc_block_start('Nested summary', with_anchor: true);
    echo '<p>Permanent nested body</p>';
    acc_block_end();
    acc_end();
    acc_item_end();

    acc_item_start('A longer title that should wrap at narrow widths', id: 'custom:ä[1]', with_anchor: true,
        header_actions: '<button type="button" class="accordion-header-action" onclick="this.dataset.clicked = \'true\'">!</button>');
    echo '<p>Explicit ID body</p>';
    acc_item_end();
    acc_block_start('Summary <em>block</em>', with_anchor: true, bg_as_collapsed: true);
    echo '<p>Permanent body</p>';
    acc_block_end();
    acc_end();

    acc_single_item_start('Single item', acc_id: 'single-set', item_id: 'single-entry', with_anchor: true);
    echo '<p>Single body</p>';
    acc_single_item_end();
    acc_single_block_start('Single block', acc_id: 'block-set', with_anchor: true);
    echo '<p>Single block body</p>';
    acc_single_block_end();
    acc_single_block_start('', with_anchor: true);
    echo '<p>Untitled block with a generated ID</p>';
    acc_single_block_end();
?>
