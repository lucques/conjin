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
            return get_global_config('module_2_location', $this->name, 'dirName');
        }

        public function is_shared(): bool {
            return get_global_config('module_2_location', $this->name, 'isShared');
        }

        public function is_external(): bool {
            return get_global_config('module_2_location', $this->name, 'isExternal');
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
            return $defs[$def_name](...$args);
        }

        public function run_preprocess_macro(PreprocessContext $c, string $name, array $args) {
            $script_path = $this->get_path() . '/preprocessing.php';
            $defs = load_defs_from_script($script_path);

            assert(isset($defs[$name]),
                "Module \"$this->name\" does not support macro \"$name\" (def \"$name\" does not exist on path \"$script_path\")");
            return $defs[$name]($c, ...$args);
        }


        ///////////////
        // Rendering //
        ///////////////

        public function render_login_with_provided_template(Module $template, LoginProfile $login_profile, bool $logout_successful, bool $password_incorrect, mixed $openid_fail, array $openid_provider_names, array $placeholders_overrides = []): void {
            $this->load_def_from_script_and_call('templates/login.php', 'render_login', $this, $template, $login_profile, $logout_successful, $password_incorrect, $openid_fail, $openid_provider_names, $placeholders_overrides);
        }

        public function render_not_found_with_provided_template(Module $template, Syslet $syslet, ?array $target_ids, array $placeholders_overrides = []): void {
            $this->load_def_from_script_and_call('templates/not_found.php', 'render_not_found', $this, $template, $syslet, $target_ids, $placeholders_overrides);
        }

        public function render_unauthorized_with_provided_template(Module $template, Syslet $syslet, ?array $target_ids, array $placeholders_overrides = []): void {
            $this->load_def_from_script_and_call('templates/unauthorized.php', 'render_unauthorized', $this, $template, $syslet, $target_ids, $placeholders_overrides);
        }
        
        public function render_target_with_provided_template(Module $template, Target $target, string $content, array $placeholders_overrides = []): void {
            $this->load_def_from_script_and_call('templates/target.php', 'render_target', $this, $template, $target, $content, $placeholders_overrides);
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
            $config_array = aux_nested_update($config_array, get_global_config_or_default(['module_2_config', $name], default: []));

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

        public function preprocess_syslet(SysletPreprocessContext $c): void {
            $defs = load_defs_from_script($this->get_path() . '/preprocessing.php');

            if (isset($defs['preprocess_syslet'])) {
                $defs['preprocess_syslet']($this, $c);
            }
        }

        public function preprocess_target(TargetPreprocessContext $c): void {
            $defs = load_defs_from_script($this->get_path() . '/preprocessing.php');

            if (isset($defs['preprocess_target'])) {
                $defs['preprocess_target']($this, $c);
            }
        }


        ////////////////
        // Processing //
        ////////////////

        public function process_syslet(Syslet $syslet, Target $target_root): void {
            $defs = load_defs_from_script($this->get_path() . '/processing.php');

            if (isset($defs['process_syslet'])) {
                $defs['process_syslet']($this, $syslet, $target_root);
            }
        }

        // Resource paths are relative to the target; included resources carry their directory separately.
        public function process_resource(Target $target, string $resource_path, string $resource_dir = 'res'): void {
            // Conjin's normal warning handler terminates the response. Optional
            // resource observers instead turn warnings into catchable exceptions.
            set_error_handler(static function(int $severity, string $message, string $file, int $line): never {
                throw new ErrorException($message, 0, $severity, $file, $line);
            });
            try {
                $defs = load_defs_from_script($this->get_path() . '/processing.php');
                if (isset($defs['process_resource'])) {
                    $defs['process_resource']($this, $target, $resource_path, $resource_dir);
                }
            } catch (Throwable) {
                // Optional observers must not prevent resource delivery.
            } finally {
                restore_error_handler();
            }
        }

        public function process_target(Target $target): void {
            $defs = load_defs_from_script($this->get_path() . '/processing.php');

            if (isset($defs['process_target'])) {
                $defs['process_target']($this, $target);
            }
        }


        ///////////////
        // Rendering //
        ///////////////

        public function render_login(LoginProfile $login_profile, bool $logout_successful, bool $password_incorrect, mixed $openid_fail, array $openid_provider_names, array $placeholders_overrides = []): void {
            $this->load_def_from_script_and_call('templates/login.php', 'render_login', $this, $this, $login_profile, $logout_successful, $password_incorrect, $openid_fail, $openid_provider_names, $placeholders_overrides);
        }

        public function render_not_found(Syslet $syslet, ?array $target_ids, array $placeholders_overrides = []): void {
            $this->load_def_from_script_and_call('templates/not_found.php', 'render_not_found', $this, $this, $syslet, $target_ids, $placeholders_overrides);
        }

        public function render_unauthorized(Syslet $syslet, ?array $target_ids, array $placeholders_overrides = []): void {
            $this->load_def_from_script_and_call('templates/unauthorized.php', 'render_unauthorized', $this, $this, $syslet, $target_ids, $placeholders_overrides);
        }
        
        public function render_target(Target $target, string $content, array $placeholders_overrides = []): void {
            $this->load_def_from_script_and_call('templates/target.php', 'render_target', $this, $this, $target, $content, $placeholders_overrides);
        }


        /////////////
        // Helpers //
        /////////////

        public function update_config(?array $config_client_new): Module {
            return new Module($this->name, aux_nested_update($this->config->root, $config_client_new));
        }
    }
?>
