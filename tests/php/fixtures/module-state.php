<?
    $GLOBALS['core_path_base'] = sys_get_temp_dir() . '/conjin-entrypoints-' . bin2hex(random_bytes(8));
    $GLOBALS['test_config'] = ['url_base' => '/', 'module_2_location' => []];
    mkdir(path('content'), recursive: true);
    mkdir(path('modules'), recursive: true);

    // Link real PHP fixture files so their definitions stay visible in source.
    $fixture_modules = __DIR__ . '/module-entrypoints/modules';
    foreach (['dependency', 'target-only', 'role', 'empty', 'macros-only'] as $name) {
        symlink($fixture_modules . '/' . $name, path('modules/' . $name));
        $GLOBALS['test_config']['module_2_location'][$name] = [
            'dirName' => $name,
            'isShared' => false,
            'isExternal' => false,
        ];
    }
