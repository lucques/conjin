<?
    // Immutable
    class ModuleLocation {
        public function __construct(public readonly string $name) {
            assert(file_exists($this->get_path()),
                "Module {$name} does not exist");
        }


        ////////////////////////
        // Derived properties //
        ////////////////////////

        public function get_dir_name(): string {
            return get_global_config('modules', $this->name, 'location', 'dirName');
        }

        public function is_shared(): bool {
            return get_global_config('modules', $this->name, 'location', 'isShared');
        }

        public function is_external(): bool {
            return get_global_config('modules', $this->name, 'location', 'isExternal');
        }

        public function get_path(): string {
            return path($this->get_module_dir());
        }
        
        public function get_url(): string {
            return url($this->get_module_dir());
        }

        public function get_css_url(): string {
            return url() . $this->get_template_css_dir();
        }


        ///////////////////////////////////////////////
        // Call module defs and preprocessing macros //
        ///////////////////////////////////////////////

        public function load_def_from_script_and_call(string $script_path_relative, string $def_name, ...$args) {
            $script_path = $this->get_path() . '/' . $script_path_relative;
            $defs = load_defs_from_script($script_path);

            assert(isset($defs[$def_name]),
                "Module \"$this->name\" does not support def \"$def_name\" on path \"$script_path\"");
            $defs[$def_name](...$args);
        }

        public function run_preprocess_macro(PreprocessContext $c, string $name, array $args) {
            $script_path = $this->get_path() . '/preprocessing.php';
            $defs = load_defs_from_script($script_path);

            assert(isset($defs[$name]),
                "Module \"$this->name\" does not support macro \"$name\" (def \"$name\" does not exist on path \"$script_path\")");
            $defs[$name]($c, ...$args);
        }


        /////////////
        // Helpers //
        /////////////
    
        private function get_module_dir(): string {
            if ($this->is_shared() && $this->is_external()) {
                return 'modules-shared-ext/' . $this->get_dir_name();
            }
            elseif ($this->is_shared() && !$this->is_external()) {
                return 'modules-shared/' . $this->get_dir_name();
            }
            elseif (!$this->is_shared() && $this->is_external()) {
                return 'modules-ext/' . $this->get_dir_name();
            }
            else {
                return 'modules/' . $this->get_dir_name();
            }
        }

        private function get_template_css_dir(): string {
            if ($this->is_shared() && $this->is_external()) {
                return 'modules-shared-ext-css/' . $this->get_dir_name();
            }
            elseif ($this->is_shared() && !$this->is_external()) {
                return 'modules-shared-css/' . $this->get_dir_name();
            }
            elseif (!$this->is_shared() && $this->is_external()) {
                return 'modules-ext-css/' . $this->get_dir_name();
            }
            else {
                return 'modules-css/' . $this->get_dir_name();
            }
        }
    }

    // Immutable
    class Module extends ModuleLocation {
        public readonly ConfigTree $config;


        //////////////////
        // Constructors //
        //////////////////

        private function __construct(
            string $name,
            ?array $config)
        {
            parent::__construct($name);
            $this->config = new ConfigTree($config ?? []);
        }

        public static function construct_with_defaults(string $name, ?array $config_client): Module {
            // Retrieve hardwired module config if available
            $defs = load_defs_from_script((new ModuleLocation($name))->get_path() . '/default_config.php');
            $config_array = isset($defs['default_config']) ? $defs['default_config']() : [];           

            // Compose with build module config if available
            $config_array = aux_nested_update($config_array, get_global_config_or_default(['modules', $name, 'config'], default: []));

            // Compose with client module config
            $config_array = aux_nested_update($config_array, $config_client ?? []);

            return new Module(
                $name,
                $config_array
            );
        }


        ///////////////////
        // Preprocessing //
        ///////////////////

        public function init_preprocessing_syslet(SysletPreprocessContext $c): void {
            $defs = load_defs_from_script($this->get_path() . '/preprocessing.php');

            if (isset($defs['init_preprocessing'])) {
                $defs['init_preprocessing']($this, $c);
            }
            if (isset($defs['init_preprocessing_syslet'])) {
                $defs['init_preprocessing_syslet']($this, $c);
            }
        }

        public function init_preprocessing_target(TargetPreprocessContext $c): void {
            $defs = load_defs_from_script($this->get_path() . '/preprocessing.php');

            if (isset($defs['init_preprocessing'])) {
                $defs['init_preprocessing']($this, $c);
            }
            if (isset($defs['init_preprocessing_target'])) {
                $defs['init_preprocessing_target']($this, $c);
            }
        }


        ///////////////
        // Rendering //
        ///////////////

        public function init_processing_syslet(Syslet $syslet, Target $target_root): void {
            $defs = load_defs_from_script($this->get_path() . '/processing_system.php');

            if (isset($defs['init_processing_syslet'])) {
                $defs['init_processing_syslet']($this, $syslet, $target_root);
            }
        }

        public function init_processing_target(Target $target): void {
            $defs = load_defs_from_script($this->get_path() . '/processing_target.php');

            if (isset($defs['init_processing_target'])) {
                $defs['init_processing_target']($this, $target);
            }
        }

        public function render_login(Syslet $syslet, bool $logout_successful, bool $password_incorrect, array $placeholders_overrides = []): void {
            $this->load_def_from_script_and_call('templates/login.php', 'render_login', $this, $syslet, $logout_successful, $password_incorrect, $placeholders_overrides);
        }

        public function render_not_found(Syslet $syslet, ?array $target_ids, array $placeholders_overrides = []): void {
            $this->load_def_from_script_and_call('templates/not_found.php', 'render_not_found', $this, $syslet, $target_ids, $placeholders_overrides);
        }
        
        public function render_target(Target $target, string $content, array $placeholders_overrides = []): void {
            $this->load_def_from_script_and_call('templates/target.php', 'render_target', $this, $target, $content, $placeholders_overrides);
        }


        /////////////
        // Helpers //
        /////////////

        public function update_config(?array $config_client_new): Module {
            return new Module($this->name, aux_nested_update($this->config->root, $config_client_new));
        }
    }
?>