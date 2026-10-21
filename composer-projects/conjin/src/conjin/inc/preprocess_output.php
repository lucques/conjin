<?
    // Do not follow symlinks when removing server-owned generated output.
    function preprocess_remove_output(string $path): void {
        if (is_link($path) || is_file($path)) {
            if (!unlink($path)) {
                throw new RuntimeException('Cannot remove ' . $path);
            }
            return;
        }
        if (!is_dir($path)) {
            return;
        }
        foreach (new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS) as $entry) {
            preprocess_remove_output($entry->getPathname());
        }
        if (!rmdir($path)) {
            throw new RuntimeException('Cannot remove directory ' . $path);
        }
    }

    function preprocess_rename_output(string $from, string $to): void {
        if (!rename($from, $to)) {
            throw new RuntimeException('Cannot rename ' . $from . ' to ' . $to);
        }
    }

    // The rename operation is injectable to exercise publication/rollback failures.
    function preprocess_publish_output(string $current, string $staging, string $old, ?callable $rename = null): void {
        $rename ??= 'preprocess_rename_output';
        if (file_exists($current) || is_link($current)) {
            $rename($current, $old);
            try {
                $rename($staging, $current);
            } catch (Throwable $error) {
                try {
                    $rename($old, $current);
                } catch (Throwable $rollback) {
                    throw new RuntimeException('Publication failed: ' . $error->getMessage()
                        . '; rollback failed: ' . $rollback->getMessage(), previous: $error);
                }
                throw new RuntimeException('Publication failed; previous output restored: ' . $error->getMessage(), previous: $error);
            }
        } else {
            $rename($staging, $current);
        }
    }

    // Returns a cleanup warning only after successful publication.
    function preprocess_replace_output(callable $generate): ?string {
        $current = $GLOBALS['core_path_preprocess_current'];
        $original = $GLOBALS['core_path_preprocess'];
        $staging = $current . '-new';
        $old = $current . '-old';
        $published = false;

        // Filesystem warnings must throw rather than invoke the global exit handler,
        // so rollback and cleanup can run. Also covers module generation writes.
        set_error_handler(function(int $severity, string $message, string $file, int $line): never {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });
        $GLOBALS['preprocess_failure_cleanup'] = function() use ($staging): void {
            unset($GLOBALS['preprocess_failure_cleanup']);
            try {
                preprocess_remove_output($staging);
            } catch (Throwable $error) {
                log_error_silently('Preprocessing staging cleanup failed: ' . $error->getMessage());
                echo "\nStaging cleanup failed: " . $error->getMessage();
            }
        };
        try {
            preprocess_remove_output($staging);
            if (!mkdir($staging, 0777)) {
                throw new RuntimeException('Cannot create staging directory.');
            }
            // Install access protection before any serialized authentication objects.
            core_save_file($staging . '/.htaccess', "Require all denied\n");
            $GLOBALS['core_path_preprocess'] = $staging;
            unset($GLOBALS['loaded_objs']);
            $generate();
            if (!is_file($staging . '/obj/target_root')) {
                throw new RuntimeException('Preprocessing did not generate target_root.');
            }
            preprocess_remove_output($old);
            preprocess_publish_output($current, $staging, $old);
            $published = true;
            unset($GLOBALS['preprocess_failure_cleanup']);
            $GLOBALS['core_path_preprocess'] = $current;
            unset($GLOBALS['loaded_objs']);
            try {
                preprocess_remove_output($old);
            } catch (Throwable $error) {
                log_error_silently('Published preprocessing output; cleanup failed: ' . $error->getMessage());
                return $error->getMessage();
            }
            return null;
        } finally {
            if (!$published) {
                $GLOBALS['core_path_preprocess'] = $original;
                unset($GLOBALS['loaded_objs']);
                if (isset($GLOBALS['preprocess_failure_cleanup'])) {
                    ($GLOBALS['preprocess_failure_cleanup'])();
                }
            }
            restore_error_handler();
        }
    }
