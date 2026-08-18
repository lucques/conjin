<?
    abstract class PreprocessContext {

        /////////////////////////////
        // Set during PASS-THROUGH //
        /////////////////////////////
       
        public array   $activated_modules;                  // dict<string, Module>
        public ?string $template;                           // key of `activated_modules`
        
        
        /////////////////
        // Constructor //
        /////////////////

        protected function __construct() {
            // Set during PASS-THROUGH
            $this->activated_modules   = [];
            $this->template            = null;
        }


        //////////////////////////////
        // Used during PASS-THROUGH //
        //////////////////////////////

        // Modules

        // @param $config: Nested assoc array (for convencience not wrapped with `ConfigTree`)
        public function activate_module(string $name, ?array $config = null): void {
            
            $m = Module::construct_with_defaults($name, $config);

            if (!isset($this->activated_modules[$name]))
            {
                // 1. Determine dependencies and (re)activate recursively
                $this->init_preprocessing($m);
                
                // 2. Only add `$module` *after* its dependencies. This way, the modules of the dependency DAG are topologically sorted.
                $this->activated_modules[$name] = $m;
            }
            // Only override previous activation if updated config is given
            elseif ($config !== null)
            {
                $this->activated_modules[$name] = $m;
            }
        }
        
        // @param $config_root: Nested assoc array (for convencience not wrapped as `ConfigTree`)
        public function update_module_config(string $name, ?array $config) {
            assert(isset($this->activated_modules[$name]), "Module `$name` not activated");
            $module = $this->activated_modules[$name];

            $module_updated = $module->update_config($config);
            
            // Re-activate
            $this->activated_modules[$name] = $module_updated;
        }


        // Template

        public function get_template(): Module {
            assert($this->template !== null, "Template not activated");

            return $this->activated_modules[$this->template];
        }

        // @param $config: Nested assoc array (for convencience not wrapped with `ConfigTree`)
        public function activate_template(string $name, ?array $config = null): void {
            $this->activate_module($name, $config);
            $this->template = $name; // Set pointer
        }

        // Preprocessing Macros

        public function run_macro(string $module_name, string $macro_name, ...$args) {
            $m = new ModuleLocation($module_name);
            $m->run_preprocess_macro($this, $macro_name, $args);
        }


        // Internal

        protected abstract function init_preprocessing(Module $m);
    }


    class SysletPreprocessContext extends PreprocessContext {
             
        public function __construct() {
            parent::__construct();
        }

        protected function init_preprocessing(Module $m) {
            $m->init_preprocessing_syslet($this);
        }
    }


    // The TargetPreprocessContext` obj is passive and just used by
    // `preprocess_target` and the various `preprocess` functions of the
    // targets for holding state. It goes through the following phases.
    enum TargetPreprocessingPhase: string {
        case CONSTRUCTED            = 'constructed';            // TargetPreprocessContext` obj has been constructed
        case PASSED_THROUGH         = 'passed_through';         // Pass through `index.php` performed
        case RECURSED               = 'recursed';               // Recursed preprocessing of child targets
        case FINISHED_PREPROCESSORS = 'finished_preprocessors'; // All `Preprocessor` objs have finished
        case DONE                   = 'done';                       
    }

    class TargetPreprocessContext extends PreprocessContext {

        // Current status
        private TargetPreprocessingPhase $phase = TargetPreprocessingPhase::CONSTRUCTED;


        ///////////////////////////////
        // Fixed during construction //
        ///////////////////////////////

        public readonly array           $target_ids;                  // list<string>
        public readonly ContentLocation $content_location;
        public readonly array           $actions_ser_2_actorlist_ser; // dict<action_serialized, list<actor_serialized>>

        public ?string $login_profile;

                
        /////////////////////////////
        // Set during PASS-THROUGH //
        /////////////////////////////

        public $name_2_preprocessor;                        // dict<string, Preprocessor>
                
        public array $children_ids;                         // array<string>

        
        //////////////////
        // Constructors //
        //////////////////

        public static function create_root(string $default_login_profile_id) {
            return new TargetPreprocessContext(
                target_ids: [],
                login_profile: $default_login_profile_id,
            );
        }

        public function create_child_ctx($child_id) {
            assert($this->phase == TargetPreprocessingPhase::PASSED_THROUGH);

            return new TargetPreprocessContext(
                target_ids: array_merge($this->target_ids, [$child_id]),
                login_profile: null,
            );
        }

        private function __construct(array $target_ids, ?string $login_profile) {
            parent::__construct();

            // Fixed during construction
            $this->target_ids = $target_ids;

            $is_inline = isset(load_defs_from_script(path_collect($target_ids, 'index.php'))['process']);
            $is_extra  = file_exists(path_collect($target_ids, 'content.php'));
            $is_redirect = isset(load_defs_from_script(path_collect($target_ids, 'index.php'))['redirect']);
            // Cannot have both redirect and content
            assert($is_inline + $is_extra + $is_redirect <= 1, 'More than one content location');

            $this->content_location =
                $is_inline ? ContentLocation::INLINE
                : ($is_extra ? ContentLocation::EXTRA
                    : ($is_redirect ? ContentLocation::REDIRECT
                        : ContentLocation::NONE));

            $this->actions_ser_2_actorlist_ser = auth_generate_actions_ser_2_actorlist_ser_for_target($target_ids);

            // Set during PASS-THROUGH
            $this->login_profile       = $login_profile;
            $this->name_2_preprocessor = [];

            $this->children_ids        = [];
        }


        //////////////////////////////
        // Used during PASS-THROUGH //
        //////////////////////////////

        // Preprocessors

        public function has_preprocessor($name) {
            return isset($this->name_2_preprocessor[$name]);
        }

        public function get_preprocessor($name) {
            return $this->name_2_preprocessor[$name];
        }
       
        public function add_preprocessor($name, Preprocessor $p) {
            assert(!isset($this->name_2_preprocessor[$name]), "Preprocessor `$name` already exists");

            $this->name_2_preprocessor[$name] = $p;
        }


        // Sub-pages

        public function add_subpage($id) {
            assert(!in_array($id, $this->children_ids), "Subpage `$id` already added");

            $this->children_ids[] = $id;
        }

        public function set_login_profile(string $name): void {
            assert(
                preg_match('/^[a-z0-9_-]+$/', $name) === 1,
                "Invalid login profile name `$name`"
            );

            $this->login_profile = $name;
        }


        ///////////////////////////////////////////////
        // Used by `preprocess_all_rec` for tracking //
        ///////////////////////////////////////////////

        public function assert_phase(TargetPreprocessingPhase $expected) {
            assert($this->phase === $expected, 'Expected phase ' . $expected->value . ', got ' . $this->phase->value);
        }

        public function change_phase(TargetPreprocessingPhase $from, TargetPreprocessingPhase $to) {
            $this->assert_phase($from);
            $this->phase = $to;
        }


        // Internal

        protected function init_preprocessing(Module $m) {
            $m->init_preprocessing_target($this);
        }
    }

    class Preprocessor {
        public function __construct(protected readonly TargetPreprocessContext $ctx) {}

        public function finish(array $id_2_child_ctx): void {
            // By default, do nothing
        }
    }


    ////////////////////
    // Main functions //
    ////////////////////

    function preprocess_syslet_context_with(callable $preprocess, string $description): SysletPreprocessContext {
        $c = new SysletPreprocessContext();

        $preprocess($c);

        // Template must have been set
        assert($c->template !== null, 'Template not set for ' . $description);

        return $c;
    }

    function preprocess_syslet_with(callable $preprocess, string $description): Syslet {
        $c = preprocess_syslet_context_with($preprocess, $description);

        return new Syslet(
            $c->activated_modules,
            $c->template
        );
    }

    function preprocess_login_profile_with(string $id, callable $preprocess): LoginProfile {
        $c = preprocess_syslet_context_with($preprocess, 'login profile `' . $id . '`');

        return new LoginProfile(
            $id,
            $c->activated_modules,
            $c->template
        );
    }

    function preprocess_syslet(string $which): Syslet {
        $script_path = path('system/' . $which . '.php');
        $defs = load_defs_from_script($script_path);

        assert(isset($defs['preprocess']), "Missing definition of `\$preprocess` in file `$script_path`");

        return preprocess_syslet_with(
            $defs['preprocess'],
            'system target `' . $which . '`'
        );
    }

    function preprocess_login_profiles(): array {
        $script_path = path('system/login.php');
        $defs = load_defs_from_script($script_path);

        assert(isset($defs['profiles']), "Missing definition of `\$profiles` in file `$script_path`");
        assert(!isset($defs['preprocess']), "`\$preprocess` is not supported in file `$script_path`; define `\$profiles` instead");
        assert(is_array($defs['profiles']), '`$profiles` must be an array');

        $id_2_preprocess = $defs['profiles'];

        foreach ($id_2_preprocess as $id => $preprocess) {
            assert(is_string($id) || is_int($id), 'Login profile IDs must be strings');
            $id = (string)$id;
            assert(
                preg_match('/^[a-z0-9_-]+$/', $id) === 1,
                "Invalid login profile ID `$id`"
            );
            assert(is_callable($preprocess), "Login profile `$id` must contain a callable");

            $id_2_preprocess[$id] = $preprocess;
        }

        $login_profiles = [];
        foreach ($id_2_preprocess as $id => $preprocess) {
            $login_profiles[$id] = preprocess_login_profile_with((string)$id, $preprocess);
        }

        return $login_profiles;
    }

    function preprocess_default_login_profile_id(array $login_profiles): string {
        $script_path = path('system/login.php');
        $defs = load_defs_from_script($script_path);

        assert(isset($defs['default_profile_id']), "Missing definition of `\$default_profile_id` in file `$script_path`");
        assert(is_string($defs['default_profile_id']), '`$default_profile_id` must be a string');

        $default_profile_id = $defs['default_profile_id'];
        assert(
            isset($login_profiles[$default_profile_id]),
            "Default login profile `$default_profile_id` does not exist"
        );

        return $default_profile_id;
    }

    function preprocess_validate_target_login_profiles(Target $target, array $login_profiles): void {
        $profile_id = $target->get_login_profile();
        $target_name = count($target->get_ids()) === 0
            ? '/'
            : implode('/', $target->get_ids()) . '/';

        assert(
            isset($login_profiles[$profile_id]),
            "Unknown login profile `$profile_id` for target `$target_name`"
        );

        foreach ($target->id_2_child as $child) {
            preprocess_validate_target_login_profiles($child, $login_profiles);
        }
    }

    function preprocess_target_root(string $default_login_profile_id): Target {
        $c = TargetPreprocessContext::create_root($default_login_profile_id);
        return preprocess_target_rec($c);
    }

    // Input:  $c is in CONSTRUCTED phase
    // Output: $c is in DONE phase
    function preprocess_target_rec(TargetPreprocessContext $c): Target {

        $c->assert_phase(TargetPreprocessingPhase::CONSTRUCTED);


        //////////////////
        // Pass-through //
        //////////////////
      
        // The dir of the target must exist.
        assert(file_exists(path_collect($c->target_ids)), 'Target dir does not exist: ' . path_collect($c->target_ids));

        // Preprocess!
        $script_path = path_collect($c->target_ids, 'index.php');
        $defs = load_defs_from_script($script_path);

        // If `preprocess` function is defined, run it
        if (isset($defs['preprocess'])) {
            load_def_from_script_and_call($script_path, 'preprocess', $c);
        }
        // Else if not defined, try default
        else {
            $script_path = path('system/target_default.php');
            $defs = load_defs_from_script($script_path);

            // If `preprocess` function is defined, run it
            if (isset($defs['preprocess'])) {
                load_def_from_script_and_call($script_path, 'preprocess', $c);
            }
            // Else, fine!
        }

        // If content exists, template must have been set
        if ($c->content_location == ContentLocation::INLINE || $c->content_location == ContentLocation::EXTRA) {
            assert($c->template !== null, 'Template not set for ' . path_collect($c->target_ids));
        }

        $c->change_phase(TargetPreprocessingPhase::CONSTRUCTED, TargetPreprocessingPhase::PASSED_THROUGH);
        

        /////////////////////////////////
        // Recurse on children targets //
        /////////////////////////////////

        $id_2_child_ctx = [];
        $id_2_child_target = [];
        foreach ($c->children_ids as $child_id) {
            $id_2_child_ctx[$child_id]    = $c->create_child_ctx($child_id);
            $id_2_child_target[$child_id] = preprocess_target_rec($id_2_child_ctx[$child_id]);
        }

        $c->change_phase(TargetPreprocessingPhase::PASSED_THROUGH, TargetPreprocessingPhase::RECURSED);


        
        //////////////////////////
        // Finish preprocessors //
        //////////////////////////
        
        // Fold and finish preprocessors
        foreach ($c->name_2_preprocessor as $preprocessor) {
            $preprocessor->finish($id_2_child_ctx);
        }

        $c->change_phase(TargetPreprocessingPhase::RECURSED, TargetPreprocessingPhase::FINISHED_PREPROCESSORS);
        

        ////////////////////////////////////////////////////////////
        // Produce `Target` obj and fill `parent` obj of children //
        ////////////////////////////////////////////////////////////

        $new_target = new Target(
            $c->activated_modules,
            $c->template,
            count($c->target_ids) > 0 ? $c->target_ids[count($c->target_ids)-1] : null,
            $c->content_location,
            $c->login_profile,
            $c->actions_ser_2_actorlist_ser,
            $id_2_child_target
        );

        foreach ($c->children_ids as $child_id) {
            // Establish circular edge back to parent
            $id_2_child_target[$child_id]->set_parent($new_target);
        }

        $c->change_phase(TargetPreprocessingPhase::FINISHED_PREPROCESSORS, TargetPreprocessingPhase::DONE);


        ////////////
        // Return //
        ////////////

        return $new_target;
    }
?>
