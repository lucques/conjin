<?
    $default_config = function(): array {
        return [
            'res_module' => null, // The module that holds the favicon
                                  // files somewhere in the `res` dir. If
                                  // this is null, the currently set
                                  // template is used.
            'path' => '/favicon', // The path to the favicons in the
                                  // module: By default, `res/favicon`
        ];
    }
?>