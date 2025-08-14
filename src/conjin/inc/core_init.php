<?
    /////////////////////////////.////////////////////////
    // Error reporting (only until loggers are set up) //
    /////////////////////////////////////////////////////

    error_reporting(E_ALL);


    ///////////
    // Timer //
    ///////////

    $GLOBALS['timer_start'] = hrtime(true);

    function timer_get_elapsed_ms() {
        return (hrtime(true) - $GLOBALS['timer_start'])/1e+6;
    }


    ////////////
    // Config //
    ////////////

    // Stateless wrapper for a nested assoc array
    class ConfigTree {
        function __construct(public readonly array $root) {}

        function get(...$path) {
            $config = $this->root;
            foreach ($path as $key) {
                $config = $config[$key];
            }
            return $config;
        }
    
        function isset(...$path) {
            $config = $this->root;
            foreach ($path as $key) {
                if (!isset($config[$key])) {
                    return false;
                }
                $config = $config[$key];
            }
            return true;
        }
        
        function get_or_default(array $path, $default) {
            if ($this->isset(...$path)) {
                return $this->get(...$path);
            }
            else {
                return $default;
            }
        }
    }

    function global_config_init(): void {
        // Check existence of config files
        assert(file_exists('../config.json'));
        assert(file_exists('../users.json'));

        // Load global config and merge `users2passwordHashes` into it
        $config               = json_decode(file_get_contents('../config.json'), associative: true);
        $users2passwordHashes = json_decode(file_get_contents('../users.json'),  associative: true);
        $config['authentication']['users2passwordHashes'] = $users2passwordHashes;

        $GLOBALS['config'] = new ConfigTree($config);

        $GLOBALS['core_path_base']       = realpath(get_global_config('path_base'));
        $GLOBALS['core_path_preprocess'] = realpath(get_global_config('path_preprocess'));

        assert($GLOBALS['core_path_base'] !== false, 'Path base does not exist.');
        assert($GLOBALS['core_path_preprocess'] !== false, 'Path to preprocess dir does not exist.');

        if (isset_global_config('errorlog_dir')) {
            $GLOBALS['core_errorlog_dir'] = realpath(get_global_config('errorlog_dir'));
            assert($GLOBALS['core_errorlog_dir'] !== false, 'Path to error log dir does not exist.');
        }

        if (isset_global_config('path_store')) {
            $GLOBALS['core_path_store'] = realpath(get_global_config('path_store'));
            assert($GLOBALS['core_path_store'] !== false, 'Path to store dir does not exist.');
        }
    }

    function get_global_config(...$path): mixed {
        return $GLOBALS['config']->get(...$path);
    }

    function get_global_config_or_default(array $path, mixed $default): mixed {
        return $GLOBALS['config']->get_or_default($path, $default);
    }

    function isset_global_config(...$path): bool {
        return $GLOBALS['config']->isset(...$path);
    }


    ////////////////////////
    // Load global config //
    ////////////////////////

    global_config_init();


    ///////////////////////////////
    // Enable composer packages  //
    ///////////////////////////////

    require __DIR__ . '/../../vendor/autoload.php';


    ///////////////////////////////
    // Error Logging & Debugging //
    ///////////////////////////////

    use Monolog\Logger;
    use Monolog\Handler\RotatingFileHandler;

    $GLOBALS['logger_error'] = new Logger('error');

    if (isset($GLOBALS['core_errorlog_dir'])) {
        $GLOBALS['logger_error']->pushHandler(new RotatingFileHandler($GLOBALS['core_errorlog_dir'] . '/error.log'));
        $GLOBALS['logger_error']->pushProcessor(new \Monolog\Processor\WebProcessor());
        $GLOBALS['logger_error']->pushProcessor(function ($record) {
            $record->extra['user'] = isset($GLOBALS['user']) ? $GLOBALS['user'] : 'unknown';
            return $record;
        });
    }

    function fail($message) {
        $GLOBALS['logger_error']->error($message);

        http_response_code(500);

        echo 'Es ist ein Fehler auf dem Server aufgetreten. Wir bitten um Entschuldigung. ';
        if (isset_global_config('errorlog_dir')) {
            echo 'Der Fehler wurde aufgezeichnet und kann behoben werden.';
        }
        else {
            echo 'Da der Fehler nicht aufgezeichnet wurde, kontaktieren Sie bitte den Administrator.';
        }

        echo "<br><br>\n\n";

        if (get_global_config('errorlog_display')) {
            echo $message;
        }

        exit();
    }

    function log_error_silently($message) {
        $GLOBALS['logger_error']->error($message);
    }

    function the_shutdown_function()
    {
        $last_error = error_get_last();
        
        if ($last_error != null) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            fail("Error code: " . $last_error['type'] . "\n\nError message:\n\n" . $last_error['message'] . "\n\nError file: " . $last_error['file'] . "\n\nError line: " . $last_error['line']);
        }
    }

    function the_error_handler($error_code, $error_msg, $error_file, $error_line)
    {
        fail("Error code: $error_code\n\nError message:\n\n$error_msg\n\nError file: $error_file\n\nError line: $error_line");
    }

    // Register error handlers
    set_error_handler("the_error_handler");
    register_shutdown_function("the_shutdown_function");


    ///////////////
    // Debugging //
    ///////////////

    function debug_var($var) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        echo '<pre>';
        print_r($var);
        echo '</pre>';
    }
?>