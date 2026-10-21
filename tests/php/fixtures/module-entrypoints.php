<?
    // Use the real loader, module dispatcher, contexts, and processables with
    // an isolated module directory and minimal configuration/authentication.
    $framework = dirname(__DIR__, 3) . '/composer-projects/conjin';
    require $framework . '/src/conjin/inc/core_pure.php';
    require $framework . '/src/conjin/inc/core_effectful.php';
    require $framework . '/src/conjin/inc/load_mech.php';
    require $framework . '/src/conjin/inc/module_mech.php';
    require $framework . '/src/conjin/inc/preprocess_mech.php';
    require $framework . '/src/conjin/inc/target.php';

    class ConfigTree {
        public function __construct(public readonly array $root) {}

        public function get(...$keys): mixed {
            $value = $this->root;
            foreach ($keys as $key) {
                $value = $value[$key];
            }
            return $value;
        }
    }

    function get_global_config(...$keys): mixed {
        return (new ConfigTree($GLOBALS['test_config']))->get(...$keys);
    }

    function get_global_config_or_default(array $keys, mixed $default): mixed {
        $value = $GLOBALS['test_config'];
        foreach ($keys as $key) {
            if (!isset($value[$key])) {
                return $default;
            }
            $value = $value[$key];
        }
        return $value;
    }

    function auth_generate_actions_ser_2_actorlist_ser_for_target(array $ids): array {
        return [];
    }


    function fixture(string $name, array $files): void {
        $dir = path('modules/' . $name);
        mkdir($dir, recursive: true);
        $GLOBALS['test_config']['module_2_location'][$name] = [
            'dirName' => $name, 'isShared' => false, 'isExternal' => false,
        ];
        foreach ($files as $file => $source) {
            file_put_contents($dir . '/' . $file, "<?\n" . $source);
        }
    }

    function remove_fixture(string $path): void {
        if (is_dir($path) && !is_link($path)) {
            foreach (scandir($path) as $name) {
                if ($name !== '.' && $name !== '..') {
                    remove_fixture($path . '/' . $name);
                }
            }
            rmdir($path);
        }
        else {
            unlink($path);
        }
    }


