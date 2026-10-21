<?
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
#[\PHPUnit\Framework\Attributes\Group('module:bootstrap-extended')]
#[\PHPUnit\Framework\Attributes\Group('module:template-interbook')]
final class ModuleEntrypointsTest extends TestCase {
    protected function setUp(): void {
        require __DIR__ . '/../fixtures/module-entrypoints.php';
        require __DIR__ . '/../fixtures/module-state.php';
    }

    protected function tearDown(): void {
        if (isset($GLOBALS['core_path_base'])) remove_fixture($GLOBALS['core_path_base']);
    }

    public function testPreprocessDispatchAndDefinitionCaching(): void {
        $GLOBALS['events'] = [];
        $target_context = TargetPreprocessContext::create_root('login');
        $target_context->activate_module('role');
        $target_context->activate_module('role');
        self::assertTrue(array_keys($target_context->activated_modules) === ['dependency', 'target-only', 'role'], 'Target dependencies must precede their consumer');
        self::assertTrue($GLOBALS['events'] === ['preprocess dependency', 'preprocess target-only', 'preprocess target'], 'Target activation must run each entrypoint once');

        $GLOBALS['events'] = [];
        $syslet_context = new SysletPreprocessContext();
        $syslet_context->activate_module('role');
        self::assertTrue(array_keys($syslet_context->activated_modules) === ['dependency', 'role'], 'Target-only dependencies must not leak into syslets');
        self::assertTrue($GLOBALS['events'] === ['preprocess syslet', 'preprocess dependency'], 'The module must control when its local helper runs');
        self::assertTrue($GLOBALS['dependency_loads'] === 1, 'Definitions must be cached while hooks still run for each context');
    }

    public function testProcessingAndOptionalEntrypoints(): void {
        $target_context = TargetPreprocessContext::create_root('login');
        $target_context->activate_module('role');
        $syslet_context = new SysletPreprocessContext();
        $syslet_context->activate_module('role');
        $target = new Target($target_context->activated_modules, null, null, ContentLocation::NONE, 'login', [], []);
        $syslet = new Syslet($syslet_context->activated_modules, 'role');
        $GLOBALS['events'] = [];
        foreach ($target->get_activated_modules() as $module) {
            $module->process_target($target);
        }
        self::assertTrue($GLOBALS['events'] === ['process dependency', 'process target-only', 'process target', 'local processing helper'], 'Target processing must preserve dependency order and explicit helper placement');

        foreach ([new Syslet($syslet_context->activated_modules, 'role'), new LoginProfile('login', $syslet_context->activated_modules, 'role')] as $syslet) {
            $GLOBALS['events'] = [];
            foreach ($syslet->activated_modules as $module) {
                $module->process_syslet($syslet, $target);
            }
            self::assertTrue($GLOBALS['events'] === ['process dependency', 'local processing helper', 'process syslet'], 'Syslets must call only their processing entrypoint');
            self::assertTrue($GLOBALS['received_syslet'] === $syslet && $GLOBALS['received_root'] === $target, 'Syslet processing must receive the actual syslet and root target');
        }

        $GLOBALS['events'] = [];
        foreach (['empty', 'macros-only', 'target-only'] as $name) {
            $module = Module::construct_with_defaults($name, null);
            $module->preprocess_syslet($syslet_context);
            $module->process_syslet($syslet, $target);
            if ($name !== 'target-only') {
                $module->preprocess_target($target_context);
                $module->process_target($target);
            }
        }
        self::assertTrue($GLOBALS['events'] === [], 'Missing hooks and files must be harmless');
        // Defined in fixtures/module-entrypoints/modules/macros-only/processing.php.
        self::assertTrue(entrypoint_fixture_macro() === 'available', 'Macros must be available without processing entrypoints');
    }

    public function testResourceObserverContextAndFailureIsolation(): void {
        $context = TargetPreprocessContext::create_root('login');
        $target = new Target([], null, null, ContentLocation::NONE, 'login', [], []);
        fixture('resource-observer', ['processing.php' => <<<'PHP'
$process_resource = function(Module $module, Target $target, string $resource_path, string $resource_dir): void {
    $GLOBALS['resource_observation'] = [$module, $target, $resource_path, $resource_dir];
    if ($resource_path === 'throw') {
        throw new RuntimeException('Observer failure');
    }
    if ($resource_path === 'warning') {
        trigger_error('Observer warning', E_USER_WARNING);
    }
};
PHP]);
        $observer = Module::construct_with_defaults('resource-observer', null);
        $observer->process_resource($target, 'example.xml', 'inc/example/res');
        self::assertSame([$observer, $target, 'example.xml', 'inc/example/res'], $GLOBALS['resource_observation']);
        $handler = static function(): never { throw new RuntimeException('Previous handler'); };
        set_error_handler($handler);
        try {
            $observer->process_resource($target, 'throw');
            $observer->process_resource($target, 'warning');
            $previous = set_error_handler($handler);
            restore_error_handler();
            self::assertSame($handler, $previous, 'Observers must restore the caller error handler');
        } finally {
            restore_error_handler();
        }
        foreach (['empty', 'macros-only', 'target-only'] as $name) {
            Module::construct_with_defaults($name, null)->process_resource($target, 'example.xml');
        }
        self::assertSame('available', entrypoint_fixture_macro());
    }

    public function testSharedTemplatesAndBootstrapInitialization(): void {
        $framework = dirname(__DIR__, 3) . '/composer-projects/conjin';
        $target_context = TargetPreprocessContext::create_root('login');
        $target_context->activate_module('role');
        $syslet_context = new SysletPreprocessContext();
        $syslet_context->activate_module('role');
        $target = new Target($target_context->activated_modules, null, null, ContentLocation::NONE, 'login', [], []);
        $syslet = new Syslet($syslet_context->activated_modules, 'role');
        // Exercise real shared modules and template forwarding, including the
        // component callers inside Bootstrap and config from the derived template.
        foreach (['src' => 'modules-shared', 'ext' => 'modules-shared-ext'] as $source => $location) {
            symlink($framework . '/' . $source . '/modules-shared', path($location));
            foreach (glob(path($location) . '/*', GLOB_ONLYDIR) as $dir) {
                $name = basename($dir);
                $GLOBALS['test_config']['module_2_location'][$name] = [
                    'dirName' => $name, 'isShared' => true, 'isExternal' => $source === 'ext',
                ];
            }
        }
        foreach ([TargetPreprocessContext::create_root('login'), new SysletPreprocessContext()] as $context) {
            $context->activate_template('template-interbook', ['sidebar' => null, 'login' => null]);
            self::assertTrue(isset($context->activated_modules['nav-view'], $context->activated_modules['bootstrap'], $context->activated_modules['doc-extensions']), 'Derived templates must forward both preprocessing entrypoints');
            self::assertTrue($context->activated_modules['dynamic-toggle']->config->get('toggles') === [], 'Template forwarding must preserve the derived configuration');
        }

        $context = TargetPreprocessContext::create_root('login');
        $context->activate_module('bootstrap-extended');
        foreach ($context->activated_modules as $module) {
            $module->process_target($target);
        }
        self::assertTrue($GLOBALS['acc_stack'] === [] && $GLOBALS['acc_next_id_numeric'] === 0, 'Bootstrap must initialize its component state through the renamed entrypoints');
        self::assertTrue(count($GLOBALS['doc_extensions_head_elements']) > 0, 'Shared processing must initialize document extensions before their consumers');
        $context = new SysletPreprocessContext();
        $context->activate_module('bootstrap', ['import_standalone_css' => false]);
        foreach ($context->activated_modules as $module) {
            $module->process_syslet($syslet, $target);
        }
        self::assertTrue(count($GLOBALS['doc_extensions_head_elements']) === 1, 'Syslet processing must reset document state and add Bootstrap exactly once');
    }

}
