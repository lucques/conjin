<?
    /*
        This module offers to build up a navigation tree of all the preprocessed
        content.

        The tree is built up mostly in the preprocessing phase, but anchors are
        added in the processing phase. This is because anchors are not known
        until the content is processed. This may change in the future!
    */

    require_once(__DIR__ . '/datatypes.php');

    ///////////////////
    // PREPROCESSING //
    ///////////////////

    class NavPreprocessor extends Preprocessor {

        //////////////////////////////
        // Used during PASS-THROUGH //
        //////////////////////////////

        public ?string $title = null;  // Title to show in navigation. Optional.


        ///////////////////////
        // Used after FINISH //
        ///////////////////////

        private ?TargetNavItem $nav_item = null;
        
        public function get_nav_item(): NavItem {
            return $this->nav_item;
        }


        ////////////
        // FINISH //
        ////////////

        public function finish(array $id_2_child_ctx): void {
            // Create nav item
            $this->nav_item = new TargetNavItem(
                target_ids:        $this->ctx->target_ids,
                nav_title:         $this->title ?? $this->ctx->title,
                privileged_groups: $this->ctx->actions_2_grouplist[auth_aux_ser_action(AUTH_VIEW_ACTION)]
            );

            // Recursively retrieve nav and add to nav item
            foreach ($id_2_child_ctx as $child_ctx) {
                if ($child_ctx->has_preprocessor('nav')) {
                    $child_nav = $child_ctx->get_preprocessor('nav')->get_nav_item();
                    
                    $this->nav_item->add_target_child($child_nav);
                }
            }

            // If root nav: Save obj
            if (count($this->nav_item->target_ids) == 0) {
                core_save_obj('nav', $this->nav_item);
            }
        }

    }

    $create_preprocessor = function(PreprocessContext $ctx): Preprocessor {
        return new NavPreprocessor($ctx);
    };

    $init_preprocessing = function(Module $module, PreprocessContext $ctx) {
        $ctx->add_preprocessor('nav', new NavPreprocessor($ctx));
    };
?>