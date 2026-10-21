<?
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class TargetSplitTest extends TestCase {
    private string $dir;

    protected function setUp(): void {
        require_once __DIR__ . '/../../../composer-projects/conjin/src/conjin/inc/module_mech.php';
        require_once __DIR__ . '/../../../composer-projects/conjin/src/conjin/inc/target.php';
        require_once __DIR__ . '/../../../composer-projects/conjin/src/conjin/inc/preprocess_mech.php';
        $this->dir = sys_get_temp_dir() . '/conjin-target-split-' . bin2hex(random_bytes(8));
        mkdir($this->dir . '/obj', recursive: true);
        $GLOBALS['target_split_test_dir'] = $this->dir;
    }

    protected function tearDown(): void {
        $entries = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($entries as $entry) {
            $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        }
        rmdir($this->dir);
    }

    public static function depths(): array {
        return ['zero' => [0], 'one' => [1], 'two' => [2], 'beyond tree' => [9]];
    }

    #[DataProvider('depths')]
    public function testSavedTreeAndLazyChunks(int $depth): void {
        $root = $this->makeTree();
        preprocess_split_target_data($root, $depth);
        $root_file = $this->dir . '/obj/target_root';
        file_put_contents($root_file, serialize($root));

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->dir . '/obj', FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $entry) {
            if ($entry->getFilename() === 'chunk') {
                $files[] = substr($entry->getPathname(), strlen($this->dir . '/obj/'));
                self::assertStringNotContainsString('O:6:"Target"', file_get_contents($entry->getPathname()));
            }
        }
        sort($files);
        $expected = match ($depth) {
            0 => [],
            1 => ['target-chunks/a/chunk', 'target-chunks/b/chunk'],
            default => ['target-chunks/a/a1/chunk', 'target-chunks/a/a2/chunk', 'target-chunks/a/chunk', 'target-chunks/b/b1/chunk', 'target-chunks/b/chunk'],
        };
        sort($expected);
        self::assertSame($expected, $files);

        $loaded = unserialize(file_get_contents($root_file));
        $a = $loaded->find_child(['a']);
        $a1 = $loaded->find_child(['a', 'a1']);
        $b = $loaded->find_child(['b']);
        self::assertSame($a, $loaded->id_2_child['a']);
        self::assertSame($a1, $a->id_2_child['a1']);
        self::assertSame($a, $a1->get_parent());
        self::assertSame(['a', 'a1'], $a1->get_ids());
        self::assertNull($loaded->find_child(['a', 'missing']));
        self::assertSame('profile-root', $a1->get_login_profile());
        self::assertSame(['read' => ['guest']], $a1->actions_ser_2_actorlist_ser);

        $lazy = new ReflectionProperty(Target::class, 'processing_modules');
        if ($depth > 0) {
            self::assertNull($lazy->getValue($a1));
            self::assertNull($lazy->getValue($b));
        }
        // JSON reflects the saved tree without loading data from a chunk.
        $json_before = json_decode(json_encode($loaded), true);
        self::assertSame($depth === 0 ? null : 'b', $json_before['id_2_child']['b']['processing_chunk']);
        if ($depth === 0) {
            self::assertSame(['template', 'feature'], array_keys($json_before['id_2_child']['b']['activated_modules']));
        }
        else {
            self::assertNull($json_before['id_2_child']['b']['activated_modules']);
            self::assertNull($lazy->getValue($b));
        }
        self::assertSame(['template', 'feature'], array_keys($a1->get_activated_modules()));
        self::assertSame('template', $a1->get_template_key());
        self::assertInstanceOf(Module::class, $a1->get_template());
        if ($depth === 1) {
            unlink($this->dir . '/obj/target-chunks/a/chunk');
            self::assertSame('template', $loaded->find_child(['a', 'a2'])->get_template_key());
        }
        if ($depth > 0) {
            self::assertNull($lazy->getValue($b));
        }

        // JSON keeps unrelated chunks unloaded even after selected data was read.
        $json = json_decode(json_encode($loaded), true);
        if ($depth === 0) {
            self::assertSame(['template', 'feature'], array_keys($json['id_2_child']['b']['activated_modules']));
        }
        else {
            self::assertNull($json['id_2_child']['b']['activated_modules']);
            self::assertNull($lazy->getValue($b));
        }
    }

    private function makeTree(): Target {
        $node = function(?string $id, array $children = []): Target {
            $template = (new ReflectionClass(Module::class))->newInstanceWithoutConstructor();
            $feature = (new ReflectionClass(Module::class))->newInstanceWithoutConstructor();
            $target = new Target(
                ['template' => $template, 'feature' => $feature],
                'template',
                $id,
                ContentLocation::NONE,
                $id === null ? 'profile-root' : null,
                ['read' => ['guest']],
                $children
            );
            foreach ($children as $child) {
                $child->set_parent($target);
            }
            return $target;
        };
        return $node(null, [
            'a' => $node('a', [
                'a1' => $node('a1'),
                'a2' => $node('a2'),
            ]),
            'b' => $node('b', ['b1' => $node('b1')]),
        ]);
    }
}

if (!function_exists('path_preprocess')) {
    function path_preprocess($suffix = '') {
        return $GLOBALS['target_split_test_dir'] . '/' . $suffix;
    }
}
if (!function_exists('core_save_file')) {
    function core_save_file($path, $content) {
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), recursive: true);
        }
        file_put_contents($path, $content);
    }
}

if (!function_exists('core_save_obj')) {
    function core_save_obj($name, $obj) {
        core_save_file(path_preprocess('obj/' . $name), serialize($obj));
    }
}
