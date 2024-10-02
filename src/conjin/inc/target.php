<?
    // Immutable
    class Processable {
        public function __construct(
            public readonly array  $activated_modules, // dict<string, Module>
            public readonly ?string $template,         // key of `activated_modules` or `null` if not set
        ) {}
        
        public function has_activated_module(string $name): bool {
            return isset($this->activated_modules[$name]);
        }

        public function get_template(): Module {
            return $this->activated_modules[$this->template];
        }
    }

    enum ContentLocation: string {
        case INLINE = 'inline';
        case EXTRA  = 'extra';
        case NONE   = 'none';
    }

    // Immutable
    class Target extends Processable implements JsonSerializable {
        // Circular pointer back to parent Target (null, if root). It is set
        // exactly once after the construction of a `Target` obj; using
        // `set_parent`. This ciruclar edge is omitted on `json_encode`.
        private ?Target $parent;

        public function __construct(
            array    $activated_modules,           // dict<string, Module>
            ?string  $template,                    // key of `activated_modules` or `null` if not set
            public readonly ?string $id,
            public readonly ContentLocation $content_location,
            public readonly array   $actions_ser_2_actorlist_ser, // dict<action_serialized, list<actor_serialized>>
            public readonly array   $id_2_child                   // dict<string, Target>
        ) {
            parent::__construct($activated_modules, $template);

            assert(!$this->content_location == ContentLocation::NONE || $template !== null, 'Contentful target must have a template');

            // Set to null pointer initially
            $null = null;
            $this->parent = $null;
        }
        
        public function get_ids(): array {
            if ($this->parent === null) {
                return []; // Root has no ids
            }
            else {
                return array_merge($this->parent->get_ids(), [$this->id]);
            }
        }
        
        public function path($suffix = ''): string {
            return path_collect($this->get_ids()) . $suffix;
        }
        
        public function get_css_slug(): string {
            return implode('_', $this->get_ids());
        }

        public function navigate_to_ids(array $relative_path): array {
            $new_ids = $this->get_ids();

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

        public function get_parent(): ?Target {
            return $this->parent;
        }

        public function has_parent(): bool {
            return $this->parent !== null;
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

        // Implement this in order to omit the `parent` field
        public function jsonSerialize(): mixed {
            return [
                'id' => $this->id,
                'content_location' => $this->content_location,
                'activated_modules' => $this->activated_modules,
                'template' => $this->template,
                'actions_ser_2_actorlist_ser' => $this->actions_ser_2_actorlist_ser,
                'id_2_child' => $this->id_2_child
            ];
        }

        public function set_parent(?Target &$parent) {
            $this->parent = $parent;
        }
    }

    // Immutable
    class Syslet extends Processable {
        public function __construct(
            array  $activated_modules, // dict<string, Module>
            string $template,          // key of `activated_modules`, must not be null
        ) {
            parent::__construct($activated_modules, $template);
        }
    }
?>