<?
    ///////////////////
    // PREPROCESSING //
    ///////////////////

    class NavPreprocessor extends Preprocessor {

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
            $title = isset($this->ctx->activated_modules['title'])
                        ? ($this->ctx->activated_modules['title']->config->get('html_short') ??
                           $this->ctx->activated_modules['title']->config->get('html') ??
                           $this->ctx->activated_modules['title']->config->get('plain') ??
                           (count($this->ctx->target_ids) > 0
                            ? $this->ctx->target_ids[count($this->ctx->target_ids)-1]
                            : 'Unbenannt'))
                        : (count($this->ctx->target_ids) > 0
                           ? $this->ctx->target_ids[count($this->ctx->target_ids)-1]
                           : 'Unbenannt');

            // Create nav item
            $this->nav_item = new TargetNavItem(
                target_ids:        $this->ctx->target_ids,
                has_content:       $this->ctx->content_location != ContentLocation::NONE,
                title:             $title,
                privileged_actors: $this->ctx->actions_ser_2_actorlist_ser[auth_aux_serialize_action(AUTH_VIEW_ACTION)]
            );

            // Recursively retrieve nav and add to nav item
            foreach ($id_2_child_ctx as $child_ctx) {
                if ($child_ctx->has_preprocessor('nav-build')) {
                    $child_nav = $child_ctx->get_preprocessor('nav-build')->get_nav_item();
                    
                    $this->nav_item->add_target_child($child_nav);
                }
            }

            // If root nav: Save obj
            if (count($this->nav_item->target_ids) == 0) {
                core_save_obj('nav', $this->nav_item);
            }
        }

    }

    $init_preprocessing_target = function(Module $m, TargetPreprocessContext $c) {
        $c->activate_module('nav-common');
        $c->activate_module('html');     // The function `nav_h` depends on `html_h`
        $c->activate_module('anchors');  // The function `nav_h` depends on `anchor_create`

        $c->add_preprocessor('nav-build', new NavPreprocessor($c));
    };
?>