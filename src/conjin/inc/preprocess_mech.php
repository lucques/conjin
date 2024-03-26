<?
    // Preprocessing works by the `preprocess` function.
    // - The content files (`index.php`) get recursively traversed
    // - For each such preprocessing pass there is a `PreprocessContext` obj
    //   that gets modified on the way.
    // - Each `PreprocessContext` obj also holds the `Preprocessor` objs
    // - These get applied after passing through the `index.php` and they
    //   fold recursively, i.e. results by sub targets are folded into the
    //   result of the parent target.
    // - Eventually the `PreprocessContext` obj carries all the information to
    //   build a `Target` obj.

    // The `PreprocessContext` obj goes through the following phases.
    enum PreprocessingPhase {
        case CONSTRUCTED;            // `PreprocessContext` obj has been constructed
        case PASSED_THROUGH;         // Pass through `index.php` performed
        case FINISHED_PREPROCESSORS; // All `Preprocessor` objs have finished
    }

    class PreprocessContext {

        // Current status
        public PreprocessingPhase $phase = PreprocessingPhase::CONSTRUCTED;


        ///////////////////////////////
        // Fixed during construction //
        ///////////////////////////////

        public readonly array $target_ids;           // array<string>
        public readonly array $parent_titles;        // array<string>
        public readonly array $actions_2_grouplist;  // dict<action_serialized, list<group_serialized>>


        ////////////////////////////////////////////
        // Constructed before/during PASS-THROUGH //
        ////////////////////////////////////////////
        
        public ?Module $template;

        public $name_2_preprocessor;

        public array $activated_modules;                 // dict<string, Module>


        /////////////////////////////
        // Set during PASS-THROUGH //
        /////////////////////////////
        
        public ?string $title;
        public array $children_ids;                      // array<string>

        
        //////////////////
        // Constructors //
        //////////////////

        public static function create_root() {
            return new PreprocessContext(
                target_ids:    [],
                parent_titles: [],
            );
        }

        public function create_child_ctx($child_id) {
            assert($this->phase == PreprocessingPhase::PASSED_THROUGH);

            return new PreprocessContext(
                target_ids:    array_merge($this->target_ids, [$child_id]),
                parent_titles: array_merge($this->parent_titles, [$this->title]),
            );
        }

        private function __construct(
            array $target_ids,
            array $parent_titles)
        {
            // Fixed during construction

            $this->target_ids          = $target_ids;
            $this->parent_titles       = $parent_titles;
            $this->actions_2_grouplist = auth_generate_actions_2_grouplist_for_target($target_ids);

            // Constructed before/during PASS-THROUGH

            $this->template            = null;

            $this->name_2_preprocessor = [];

            $this->activated_modules   = []; // To be initialized


            // Set during PASS-THROUGH
            
            $this->title = null;
            $this->children_ids = [];
        }


        //////////////////////////////
        // Used during PASS-THROUGH //
        //////////////////////////////

        public function get_last_target_id() {
            return $this->target_ids[count($this->target_ids) - 1];
        }

        public function has_preprocessor($name) {
            return isset($this->name_2_preprocessor[$name]);
        }

        public function get_preprocessor($name) {
            return $this->name_2_preprocessor[$name];
        }

        public function add_subpage($id) {
            $this->children_ids[] = $id;
        }

        // @param $config: Nested assoc array (for convencience not wrapped with `ConfigTree`)
        public function activate_template(string $name, array $config = null): void
        {
            $this->activate_module($name);
            $this->template = $this->activated_modules[$name];
        }
        
        public function activate_module(string $name, array $config = null): void
        {
            $m = Module::construct($name, $config);

            if (!isset($this->activated_modules[$name]))
            {
                // 1. Determine dependencies and (re)activate recursively
                $m->init_preprocessing($this);
                
                // 2. Only add `$module` *after* its dependencies. This way, the modules of the dependency DAG are topologically sorted.
                $this->activated_modules[$name] = $m;
            }
            // Only override previous activation if updated config is given
            elseif ($config !== null)
            {
                $this->activated_modules[$name] = $m;
            }
        }

        public function add_preprocessor($name, Preprocessor $p) {
            $this->name_2_preprocessor[$name] = $p;
        }
        
        // @param $config_root: Nested assoc array (for convencience not wrapped as `ConfigTree`)
        public function update_module_config(string $name, array $config_root) {
            assert(isset($this->activated_modules[$name]), "Module `$name` not activated");
            $module = $this->activated_modules[$name];

            $updated_config = aux_update_config($module->config->root, $config_root);
            $updated_module = new Module($module->dir_name, $module->is_shared, $module->is_external, $updated_config);

            // Re-activate
            $this->activated_modules[$name] = $updated_module;
        }

        // @param $config_root: Nested assoc array (for convencience not wrapped as `ConfigTree`)
        public function update_template_config(array $config_root) {
            assert(isset($this->template), "Template not activated");

            $updated_config   = aux_update_config($this->template->config->root, $config_root);
            $updated_template = new Module($this->template->dir_name, $this->template->is_shared, $this->template->is_external, $updated_config);

            // Re-set
            $this->template = $updated_template;
        }

        public function path($suffix) {
            return path_collect($this->target_ids) . '/' . $suffix;
        }
    }

    class Preprocessor {
        public function __construct(protected readonly PreprocessContext $ctx) {}

        public function finish(array $id_2_child_ctx): void {
            // By default, do nothing
        }
    }

    // Main function
    function preprocess_all(): Target {
        $ctx = PreprocessContext::create_root();
        return preprocess($ctx);
    }

    // Input:  $ctx is in CONSTRUCTED phase
    // Output: $ctx is in FINISHED_PREPROCESSORS phase
    function preprocess(PreprocessContext $ctx): Target {

        assert($ctx->phase == PreprocessingPhase::CONSTRUCTED);


        //////////////////
        // Pass-through //
        //////////////////

        $script_path = path_collect($ctx->target_ids) . '/index.php';
        $defs = load_defs_from_script($script_path);
        assert(isset($defs['preprocess']), "Missing `\$preprocess` function in `$script_path`");

        // Preprocess!
        $defs['preprocess']($ctx);

        // Check consistency of the context's state
        assert($ctx->title    != null, 'Title not set for '    . path_collect($ctx->target_ids));
        assert($ctx->template != null, 'Template not set for ' . path_collect($ctx->target_ids));

        $ctx->phase = PreprocessingPhase::PASSED_THROUGH;
        

        /////////////////////////////////
        // Recurse on children targets //
        /////////////////////////////////

        $id_2_child_ctx = [];
        $id_2_child_target = [];
        foreach ($ctx->children_ids as $child_id) {
            $id_2_child_ctx[$child_id]    = $ctx->create_child_ctx($child_id);
            $id_2_child_target[$child_id] = preprocess($id_2_child_ctx[$child_id]);
        }

        
        //////////////////////////
        // Finish preprocessors //
        //////////////////////////
        
        // Fold and finish preprocessors
        foreach ($ctx->name_2_preprocessor as $preprocessor) {
            $preprocessor->finish($id_2_child_ctx);
        }

        $ctx->phase = PreprocessingPhase::FINISHED_PREPROCESSORS;
        

        //////////////////////////
        // Produce `target` obj //
        //////////////////////////

        return new Target(
            $ctx->target_ids,
            array_merge($ctx->parent_titles, [$ctx->title]),
            $ctx->template,
            $ctx->activated_modules,
            $ctx->actions_2_grouplist,
            $id_2_child_target
        );
    }
?>