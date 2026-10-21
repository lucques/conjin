<?
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class ResourceFilesTest extends TestCase {
    private string $owner;

    protected function setUp(): void {
        require_once dirname(__DIR__, 3) . '/composer-projects/conjin/src/conjin/inc/resource_files.php';
        $this->owner = sys_get_temp_dir() . '/conjin-resources-' . bin2hex(random_bytes(8)) . '/module';
        mkdir($this->owner . '/res', 0777, true);
        foreach (['print.css', 'print.css.map', 'processing.php', 'res/public.js', 'res/space & name.txt', 'res/.secret', 'res/secret.PHP'] as $file) {
            file_put_contents($this->owner . '/' . $file, 'fixture');
        }
        foreach (['escape.txt' => '../processing.php', 'source.txt' => 'secret.PHP', 'valid-link.js' => 'public.js'] as $name => $target) {
            symlink($target, $this->owner . '/res/' . $name);
        }
        symlink(dirname($this->owner), $this->owner . '/outside-res');
    }

    protected function tearDown(): void {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname($this->owner), FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            if ($file->isDir() && !$file->isLink()) rmdir($file->getPathname());
            else unlink($file->getPathname());
        }
        rmdir(dirname($this->owner));
    }

    public static function paths(): array {
        return [
            'stylesheet' => ['', 'print.css', 'print.css'],
            'source map' => ['', 'print.css.map', 'print.css.map'],
            'empty' => ['', '', null],
            'parent' => ['', '../secret.css', null],
            'PHP source' => ['', 'processing.php', null],
            'public script' => ['res', 'public.js', 'res/public.js'],
            'spaces' => ['res', 'space & name.txt', 'res/space & name.txt'],
            'contained symlink' => ['res', 'valid-link.js', 'res/public.js'],
            'source traversal' => ['res', '../processing.php', null],
            'missing' => ['res', 'missing.js', null],
            'hidden' => ['res', '.secret', null],
            'uppercase source' => ['res', 'secret.PHP', null],
            'escaping symlink' => ['res', 'escape.txt', null],
            'source symlink' => ['res', 'source.txt', null],
            'absolute' => ['res', '/etc/passwd', null],
            'backslash' => ['res', '..\\processing.php', null],
            'null byte' => ['res', "public.js\0.txt", null],
            'escaping resource root' => ['outside-res', 'module/processing.php', null],
            'traversing resource root' => ['../module/res', 'public.js', null],
            'directory' => ['res', '.', null],
        ];
    }

    #[DataProvider('paths')]
    public function testResourceContainment(string $directory, string $file, ?string $expected): void {
        self::assertSame($expected === null ? null : $this->owner . '/' . $expected, resolve_resource_file($this->owner, $directory, $file));
    }
}
