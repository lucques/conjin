<?
    // Public files must stay within an explicitly selected resource directory.
    // Neither request paths nor symlinks may reach implementation files.
    function resolve_resource_file(string $owner, string $resource_dir, string $resource): ?string {
        if ($resource === '') {
            return null;
        }
        foreach ([$resource_dir, $resource] as $relative) {
            if (str_contains($relative, "\0") || str_contains($relative, '\\') ||
                str_starts_with($relative, '/') || preg_match('~(?:^|/)\.~', $relative)) {
                return null;
            }
        }
        if (preg_match('~\.(?:php[0-9]*|phtml|phar|inc)(?:[./]|$)~i', $resource)) {
            return null;
        }
        $owner = realpath($owner);
        if ($owner === false) {
            return null;
        }
        $root = $resource_dir === '' ? $owner : realpath($owner . '/' . $resource_dir);
        if ($root === false || ($resource_dir !== '' && !str_starts_with($root, $owner . '/'))) {
            return null;
        }
        $file = realpath($root . '/' . $resource);
        if ($file === false || !is_file($file) || !is_readable($file) || !str_starts_with($file, $root . '/')) {
            return null;
        }
        // Check the resolved name too: a harmless-looking link must not expose PHP.
        if (preg_match('~(?:^|/)\.|\.(?:php[0-9]*|phtml|phar|inc)(?:[./]|$)~i', substr($file, strlen($root) + 1))) {
            return null;
        }
        return $file;
    }
