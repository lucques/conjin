<?
    // Public static module assets do not require sessions or preprocessed state.
    chdir(__DIR__);
    header('Cache-Control: no-store');
    require 'inc/cache.php';
    require 'inc/core_pure.php';
    require 'inc/resource_files.php';

    $group = $_GET['group'] ?? null;
    $module = $_GET['module'] ?? null;
    $resource = $_GET['res'] ?? null;
    if (!is_string($group) || !preg_match('/^modules(?:-shared)?(?:-ext)?(?:-css)?$/D', $group) ||
        !is_string($module) || !preg_match('/^[a-zA-Z0-9_.-]+$/D', $module) ||
        !is_string($resource)) {
        http_response_code(404);
        exit;
    }
    $config = json_decode(file_get_contents('../config.json'), associative: true, flags: JSON_THROW_ON_ERROR);
    $source_group = preg_replace('/-css$/', '', $group);
    $configured = false;
    foreach ($config['module_2_location'] as $location) {
        $location_group = 'modules' . ($location['isShared'] ? '-shared' : '') . ($location['isExternal'] ? '-ext' : '');
        if ($location_group === $source_group && $location['dirName'] === $module) {
            $configured = true;
            break;
        }
    }
    $compiled = str_ends_with($group, '-css');
    // Compiled CSS and maps live at the compiler output root (or subdirectories).
    // Other asset types must still be under res/; implementation files stay private.
    $public_output = !$compiled || str_starts_with($resource, 'res/') || preg_match('/\.css(?:\.map)?$/D', $resource);
    $file = $configured && $public_output
        ? resolve_resource_file($config['path_base'] . '/' . $group . '/' . $module, $compiled ? '' : 'res', $resource)
        : null;
    if ($file === null) {
        http_response_code(404);
        exit;
    }
    if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
        header('Allow: GET, HEAD');
        http_response_code(405);
        exit;
    }
    header_remove('Cache-Control');
    check_whether_unmodified_and_handle_and_exit($file, true);
    $mime = match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
        'css' => 'text/css',
        'js', 'mjs' => 'application/javascript',
        'json', 'map' => 'application/json',
        'wasm' => 'application/wasm',
        'svg' => 'image/svg+xml',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        default => get_mime($file),
    };
    header('Content-Type: ' . $mime);
    header('X-Content-Type-Options: nosniff');
    header('Content-Length: ' . filesize($file));
    if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
        readfile($file);
    }
