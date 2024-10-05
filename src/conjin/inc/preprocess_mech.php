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

                
        /////////////////////////////
        // Set during PASS-THROUGH //
        /////////////////////////////

        public $name_2_preprocessor;                        // dict<string, Preprocessor>
                
        public array $children_ids;                         // array<string>

        
        //////////////////
        // Constructors //
        //////////////////

        public static function create_root() {
            return new TargetPreprocessContext(
                target_ids: [],
            );
        }

        public function create_child_ctx($child_id) {
            assert($this->phase == TargetPreprocessingPhase::PASSED_THROUGH);

            return new TargetPreprocessContext(
                target_ids: array_merge($this->target_ids, [$child_id]),
            );
        }

        private function __construct(array $target_ids) {
            parent::__construct();

            // Fixed during construction
            $this->target_ids = $target_ids;
            $this->content_location =
                isset(load_defs_from_script(path_collect($target_ids) . '/index.php')['process'])
                ? ContentLocation::INLINE
                : (file_exists(path_collect($target_ids) . '/content.php')
                    ? ContentLocation::EXTRA
                    : ContentLocation::NONE);
            $this->actions_ser_2_actorlist_ser = auth_generate_actions_ser_2_actorlist_ser_for_target($target_ids);

            // Set during PASS-THROUGH
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

    function preprocess_syslet(string $which): Syslet {
        $c = new SysletPreprocessContext();

        // Preprocess!
        $script_path = path('system/' . $which . '.php');
        load_def_from_script_and_call($script_path, 'preprocess', $c);

        // Template must have been set
        assert($c->template !== null, 'Template not set for system target `' . $which . '`');
        
        return new Syslet(
            $c->activated_modules,
            $c->template
        );
    }

    function preprocess_target_root(): Target {
        $c = TargetPreprocessContext::create_root();
        return preprocess_target_rec($c);
    }

    // Input:  $c is in CONSTRUCTED phase
    // Output: $c is in DONE phase
    function preprocess_target_rec(TargetPreprocessContext $c): Target {

        $c->assert_phase(TargetPreprocessingPhase::CONSTRUCTED);


        //////////////////
        // Pass-through //
        //////////////////
      
        // Preprocess!
        $script_path = path_collect($c->target_ids) . '/index.php';
        $defs = load_defs_from_script($script_path);

        // If `preprocess` function is defined, run it
        if (isset($defs['preprocess'])) {
            load_def_from_script_and_call($script_path, 'preprocess', $c);
        }
        // Else if defined, use default
        else {
            $script_path = path('system/target_default.php');
            $defs = load_defs_from_script($script_path);

            if (isset($defs['preprocess'])) {
                load_def_from_script_and_call($script_path, 'preprocess', $c);
            }
        }

        // If content exists, template must have been set
        assert($c->content_location == ContentLocation::NONE || $c->template !== null, 'Template not set for ' . path_collect($c->target_ids));

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