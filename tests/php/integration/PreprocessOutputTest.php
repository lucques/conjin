<?
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses]
#[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
final class PreprocessOutputTest extends TestCase {
    private string $parent;
    private string $current;

    protected function setUp(): void {
        require __DIR__ . '/../../../composer-projects/conjin/src/conjin/inc/core_effectful.php';
        require __DIR__ . '/../../../composer-projects/conjin/src/conjin/inc/preprocess_output.php';
        function log_error_silently(string $message): void {
            $GLOBALS['preprocess_test_errors'][] = $message;
        }
        $this->parent = sys_get_temp_dir() . '/conjin-output-' . bin2hex(random_bytes(8));
        mkdir($this->parent);
        $this->current = $this->parent . '/custom-dyn-preprocessing';
        $GLOBALS['core_path_preprocess_current'] = $this->current;
        $GLOBALS['core_path_preprocess'] = $this->current;
        unset($GLOBALS['loaded_objs']);
    }

    protected function tearDown(): void {
        preprocess_remove_output($this->parent);
        unset($GLOBALS['preprocess_failure_cleanup'], $GLOBALS['loaded_objs']);
    }

    private function generate(): void {
        self::assertSame("Require all denied\n", file_get_contents(path_preprocess('.htaccess')));
        core_save_obj('target_root', 'new');
        core_save_res('new.txt', 'new resource');
    }

    public function testFirstPublicationAndCompleteReplacement(): void {
        self::assertNull(preprocess_replace_output(fn() => $this->generate()));
        core_save_file($this->current . '/res/obsolete.txt', 'obsolete');
        core_save_file($this->current . '-new/obj/incomplete', 'stale staging');
        core_save_file($this->current . '-old/obj/stale', 'leftover old output');
        self::assertNull(preprocess_replace_output(function(): void {
            self::assertFileExists($this->current . '/res/obsolete.txt');
            self::assertFileDoesNotExist(path_preprocess('obj/incomplete'));
            $this->generate();
        }));
        self::assertSame('new', unserialize(file_get_contents($this->current . '/obj/target_root')));
        self::assertFileDoesNotExist($this->current . '/res/obsolete.txt');
        self::assertDirectoryDoesNotExist($this->current . '-new');
        self::assertDirectoryDoesNotExist($this->current . '-old');
    }

    public function testFailedGenerationPreservesCurrentAndRemovesStaging(): void {
        core_save_obj('target_root', 'previous');
        try {
            preprocess_replace_output(function(): void {
                $this->generate();
                throw new RuntimeException('generation failed');
            });
            self::fail('Generation should fail');
        } catch (RuntimeException $error) {
            self::assertSame('generation failed', $error->getMessage());
        }
        self::assertSame('previous', unserialize(file_get_contents($this->current . '/obj/target_root')));
        self::assertDirectoryDoesNotExist($this->current . '-new');
        self::assertSame($this->current . '/', path_preprocess());
    }

    public function testWriteFailurePreventsPublication(): void {
        core_save_obj('target_root', 'previous');
        try {
            preprocess_replace_output(function(): void {
                core_save_file(path_preprocess('obj'), 'blocks object directory');
                $this->generate();
            });
            self::fail('Write should fail');
        } catch (ErrorException $error) {
            self::assertStringContainsString('mkdir', $error->getMessage());
        }
        self::assertSame('previous', unserialize(file_get_contents($this->current . '/obj/target_root')));
        self::assertDirectoryDoesNotExist($this->current . '-new');
    }

    public function testCleanupWarningAndBlockedSubsequentPublication(): void {
        core_save_obj('target_root', 'previous');
        $warning = preprocess_replace_output(function(): void {
            $this->generate();
            chmod($this->current, 0555);
        });
        try {
            self::assertNotNull($warning);
            self::assertNotEmpty($GLOBALS['preprocess_test_errors']);
            self::assertSame('new', unserialize(file_get_contents($this->current . '/obj/target_root')));
            self::assertDirectoryExists($this->current . '-old');
            try {
                preprocess_replace_output(function(): void {
                    core_save_obj('target_root', 'unpublished');
                });
                self::fail('Leftover old output must block publication when cleanup fails');
            } catch (ErrorException) {
            }
            self::assertSame('new', unserialize(file_get_contents($this->current . '/obj/target_root')));
            self::assertDirectoryDoesNotExist($this->current . '-new');
        } finally {
            chmod($this->current . '-old', 0777);
        }
        self::assertNull(preprocess_replace_output(fn() => $this->generate()));
        self::assertDirectoryDoesNotExist($this->current . '-old');
    }

    public function testPublicationFailureRollsBack(): void {
        core_save_obj('target_root', 'previous');
        core_save_file($this->current . '-new/obj/target_root', serialize('new'));
        $calls = 0;
        try {
            preprocess_publish_output($this->current, $this->current . '-new', $this->current . '-old',
                function(string $from, string $to) use (&$calls): void {
                    if (++$calls === 2) {
                        throw new RuntimeException('injected publication failure');
                    }
                    preprocess_rename_output($from, $to);
                });
            self::fail('Publication should fail');
        } catch (RuntimeException $error) {
            self::assertStringContainsString('previous output restored', $error->getMessage());
        }
        self::assertSame(3, $calls);
        self::assertSame('previous', unserialize(file_get_contents($this->current . '/obj/target_root')));
        self::assertDirectoryDoesNotExist($this->current . '-old');
    }

    public function testRollbackFailureIsReportedAndKeepsOldOutput(): void {
        core_save_obj('target_root', 'previous');
        core_save_file($this->current . '-new/obj/target_root', serialize('new'));
        $calls = 0;
        try {
            preprocess_publish_output($this->current, $this->current . '-new', $this->current . '-old',
                function(string $from, string $to) use (&$calls): void {
                    if (++$calls >= 2) {
                        throw new RuntimeException('injected rename failure');
                    }
                    preprocess_rename_output($from, $to);
                });
            self::fail('Publication should fail');
        } catch (RuntimeException $error) {
            self::assertStringContainsString('rollback failed', $error->getMessage());
        }
        self::assertDirectoryDoesNotExist($this->current);
        self::assertSame('previous', unserialize(file_get_contents($this->current . '-old/obj/target_root')));
    }
}
