<?
    // Immutable
    class Target {
        public function __construct(
            public readonly array          $ids,                  // array<string>
            public readonly array          $all_titles,           // array<string>
            public readonly Module         $template,
            public readonly array          $activated_modules,    // dict<string, Module>       
            public readonly array          $actions_2_grouplist,  // dict<action_serialized, list<group_serialized>>
            public readonly array          $id_2_child            // dict<string, Target>
        ) {}
        
        public function has_activated_module(string $name): bool {
            return isset($this->activated_modules[$name]);
        }
        
        public function path($suffix = ''): string {
            return path_collect($this->ids) . $suffix;
        }
        
        public function get_css_slug(): string {
            return implode('_', $this->ids);
        }

        public function navigate_to_ids(array $relative_path): array {
            $new_ids = $this->ids;

            foreach ($relative_path as $id) {
                if ($id == '..') {
                    assert($new_ids !== [], 'Cannot go up in the hierarchy');
                    array_pop($new_ids);
                }
                else {
                    $new_ids[] = $id;
                }
            }

            return $new_ids;
        }

        public function find_child(array $ids): ?Target {
            if (count($ids) == 0) {
                return $this;
            }
            else {
                if (isset($this->id_2_child[$ids[0]])) {
                    return $this->id_2_child[$ids[0]]->find_child(array_slice($ids, 1));
                }
                else {
                    return null;
                }
            }
        }

    }
?>