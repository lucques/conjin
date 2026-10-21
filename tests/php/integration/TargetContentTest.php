<?
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class TargetContentTest extends TestCase {
    protected function setUp(): void {
        require __DIR__ . '/../fixtures/module-entrypoints.php';
        require dirname(__DIR__, 3) . '/composer-projects/conjin/src/conjin/inc/process_mech.php';

        $GLOBALS['core_path_base'] = sys_get_temp_dir() . '/conjin-target-content-' . bin2hex(random_bytes(8));
        $GLOBALS['test_config'] = ['url_base' => '/', 'module_2_location' => []];
        $GLOBALS['render_events'] = [];

        fixture('template', [
            'processing.php' => <<<'PHP'
    $process_target = function (Module $m, Target $target) {
        $GLOBALS['render_events'][] = 'module';
        echo 'Module initialization output must be discarded.';
    };
PHP,
        ]);
        $this->writeFile('modules/template/templates/target.php', <<<'PHP'
<?
    $render_target = function (Module $m, Module $template, Target $target, string $content, array $overrides) {
        $GLOBALS['render_events'][] = 'template';
        echo '<main>' . $content . '</main>';
    };
PHP);
        $this->writeFile('system/target_default.php', <<<'PHP'
<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template');
    };
PHP);
        $this->writeFile('content/content.php', '<? throw new RuntimeException("Legacy content must not execute");');
    }

    protected function tearDown(): void {
        if (isset($GLOBALS['core_path_base'])) {
            remove_fixture($GLOBALS['core_path_base']);
        }
    }

    private function writeFile(string $relative, string $source): void {
        $file = path($relative);
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), recursive: true);
        }
        file_put_contents($file, $source);
    }

    public function testInlineContentUsesDefaultPreprocessingAndRendersThroughTemplate(): void {
        $this->writeFile('content/index.php', <<<'PHP'
<?
    $process = function (Target $target) {
        $GLOBALS['render_events'][] = 'content';
        echo '<p>' . $target->get_login_profile() . '</p>';
    };
PHP);

        $target = preprocess_target_root('login');
        self::assertSame(ContentLocation::INLINE, $target->content_location);
        self::assertSame('template', $target->get_template_key());
        self::assertSame([], $GLOBALS['render_events'], 'Preprocessing must not render content');

        $this->expectOutputString('<main><p>login</p></main>');
        process($target);
        self::assertSame(['module', 'content', 'template'], $GLOBALS['render_events']);
    }

    public static function splitDepths(): array {
        return ['zero' => [0], 'one' => [1], 'two' => [2], 'beyond tree' => [9]];
    }

    #[DataProvider('splitDepths')]
    public function testChildRendersAfterSplitAndReload(int $depth): void {
        $this->writeFile('content/index.php', <<<'PHP'
<?
    $preprocess = function(TargetPreprocessContext $c) {
        $c->activate_template('template');
        $c->add_subpage('child');
    };
    $process = function(Target $target) {};
PHP);
        $this->writeFile('content/child/index.php', <<<'PHP'
<?
    $process = function(Target $target) {
        $GLOBALS['render_events'][] = 'content';
        echo '<p>' . $target->get_login_profile() . '</p>';
    };
PHP);
        $GLOBALS['core_path_preprocess'] = $GLOBALS['core_path_base'] . '/preprocess';
        mkdir($GLOBALS['core_path_preprocess'] . '/obj', recursive: true);

        $root = preprocess_target_root('login');
        preprocess_split_target_data($root, $depth);
        core_save_obj('target_root', $root);
        unset($root);
        $loaded = unserialize(file_get_contents(path_preprocess('obj/target_root')));
        $child = $loaded->find_child(['child']);
        self::assertSame($child, $loaded->id_2_child['child']);
        self::assertSame($loaded, $child->get_parent());
        if ($depth > 0) {
            self::assertNull((new ReflectionProperty(Target::class, 'processing_modules'))->getValue($child));
        }

        $this->expectOutputString('<main><p>login</p></main>');
        process($child);
        self::assertSame(['module', 'content', 'template'], $GLOBALS['render_events']);
    }

    public static function nonRenderingTargets(): array {
        return [
            'only legacy content' => [null, 'none'],
            'redirect with legacy content' => ['<? $redirect = ["destination"];', 'redirect'],
        ];
    }

    #[DataProvider('nonRenderingTargets')]
    public function testTargetsWithoutProcessCannotRender(?string $index, string $location): void {
        if ($index !== null) {
            $this->writeFile('content/index.php', $index);
        }

        $target = preprocess_target_root('login');
        self::assertSame($location, $target->content_location->value);
        self::assertSame([], $GLOBALS['render_events']);

        $this->expectException(AssertionError::class);
        $this->expectExceptionMessage('Can only process targets with inline content location');
        process($target);
    }

    public function testInlineContentAndRedirectRemainMutuallyExclusive(): void {
        $this->writeFile('content/index.php', <<<'PHP'
<?
    $process = function (Target $target) {};
    $redirect = ['destination'];
PHP);

        $this->expectException(AssertionError::class);
        $this->expectExceptionMessage('More than one content location');
        preprocess_target_root('login');
    }
}
