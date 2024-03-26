<?
    // Immutable
    class ModuleLocation {
        // TODO make private?
        public function __construct(
            public readonly string $dir_name,
            public readonly bool $is_shared,
            public readonly bool $is_external
        ) {
            assert(file_exists($this->get_path()),
                "Module {$this->dir_name} does not exist");
        }

        public static function construct(string $name): ModuleLocation {
            return new ModuleLocation(
                get_global_config('modules', $name, 'location', 'dirName'),
                get_global_config('modules', $name, 'location', 'isShared'),
                get_global_config('modules', $name, 'location', 'isExternal'));
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

        private function get_module_dir(): string {
            if ($this->is_shared && $this->is_external) {
                return 'modules-shared-ext/' . $this->dir_name;
            }
            elseif ($this->is_shared && !$this->is_external) {
                return 'modules-shared/' . $this->dir_name;
            }
            elseif (!$this->is_shared && $this->is_external) {
                return 'modules-ext/' . $this->dir_name;
            }
            else {
                return 'modules/' . $this->dir_name;
            }
        }

        private function get_template_css_dir(): string {
            if ($this->is_shared && $this->is_external) {
                return 'modules-shared-ext-css/' . $this->dir_name;
            }
            elseif ($this->is_shared && !$this->is_external) {
                return 'modules-shared-css/' . $this->dir_name;
            }
            elseif (!$this->is_shared && $this->is_external) {
                return 'modules-ext-css/' . $this->dir_name;
            }
            else {
                return 'modules-css/' . $this->dir_name;
            }
        }
        

        //////////////////////////////////////////////
        // Module functions via loading and caching //
        //////////////////////////////////////////////

        public function may_create_preprocessor(): bool {
            $defs = load_defs_from_script($this->get_path() . '/preprocessing.php');

            return isset($defs['create_preprocessor']);
        }

        public function create_preprocessor(PreprocessContext $ctx): Preprocessor {
            $defs = load_defs_from_script($this->get_path() . '/preprocessing.php');

            assert(isset($defs['create_preprocessor']),
                "Module \"$this->dir_name\" does not support preprocessing");
            return $defs['create_preprocessor']($ctx);
        }

        public function init_preprocessing(PreprocessContext $ctx): void {
            $defs = load_defs_from_script($this->get_path() . '/preprocessing.php');

            if (isset($defs['init_preprocessing'])) {
                $defs['init_preprocessing']($this, $ctx);
            }
        }

        public function init_processing(Target $target): void {
            $defs = load_defs_from_script($this->get_path() . '/processing.php');

            if (isset($defs['init_processing'])) {
                $defs['init_processing']($this, $target);
            }
        }

        public function render_component_for_target(string $component, Target $target): void {
            $script_path = $this->get_path() . '/inc/' . $component . '.php';
            $defs = load_defs_from_script($script_path);

            assert(isset($defs['render_for_target']),
                "Module \"$this->dir_name\" does not support component \"$component\" to render for target");
            $defs['render_for_target']($this, $target);
        }

        public function render_component_for_target_with_sub_template(Module $sub_template, string $component, Target $target): void {
            $script_path = $sub_template->get_path() . '/inc/' . $component . '.php';
            $defs = load_defs_from_script($script_path);

            assert(isset($defs['render_for_target_with_sub_template']),
                "Module \"$sub_template->dir_name\" does not support component \"$component\" to render for target with sub template");
            $defs['render_for_target_with_sub_template']($this, $sub_template, $target);
        }

        public function render_component_generic(string $component, Target $root_target, array $params): void {
            $script_path = $this->get_path() . '/inc/' . $component . '.php';
            $defs = load_defs_from_script($script_path);

            assert(isset($defs['render_generic']),
                "Module \"$this->dir_name\" does not support component \"$component\" to render generically");
            $defs['render_generic']($this, $root_target, $params);
        }

        public function render_component_generic_with_sub_template(Module $sub_template, string $component, Target $root_target, array $params): void {
            $script_path = $sub_template->get_path() . '/inc/' . $component . '.php';
            $defs = load_defs_from_script($script_path);

            assert(isset($defs['render_generic_with_sub_template']),
                "Module \"$sub_template->dir_name\" does not support component \"$component\" to render generically with sub template");
            $defs['render_generic_with_sub_template']($this, $sub_template, $root_target, $params);
        }
    }

    // Immutable
    class Module extends ModuleLocation {
        public readonly ConfigTree $config;

        public function __construct(
            string $dir_name,
            bool $is_shared,
            bool $is_external,
            ?array $config = null)
        {
            parent::__construct($dir_name, $is_shared, $is_external);

            if ($config === null) {
                $this->config = new ConfigTree(get_global_config_or_default(['modules', $this->dir_name, 'defaultConfig'], default: []));
            }
            else {
                $this->config = new ConfigTree($config);
            }
        }

        public static function construct(string $name, ?array $config = null): Module {
            return new Module(
                get_global_config('modules', $name, 'location', 'dirName'),
                get_global_config('modules', $name, 'location', 'isShared'),
                get_global_config('modules', $name, 'location', 'isExternal'),
                $config);
        }
    }
?>