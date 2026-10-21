<?
    abstract class Processable {
        abstract public function get_activated_modules(): array;
        abstract public function get_template_key(): ?string;

        public function has_activated_module(string $name): bool {
            return isset($this->get_activated_modules()[$name]);
        }

        public function get_template(): Module {
            return $this->get_activated_modules()[$this->get_template_key()];
        }
    }

    enum ContentLocation: string {
        case INLINE   = 'inline';   // There is a `$process` def in `index.php`
        case REDIRECT = 'redirect'; // There is a `$redirect` def in `index.php` pointing to target ids
        case NONE     = 'none';     // No content
    }

    class Target extends Processable implements JsonSerializable {
        // Circular pointer to the parent Target, null for the root.
        // Preprocessing sets it after construction; jsonSerialize() omits this
        // back-reference to avoid a cycle in debug JSON.
        private ?Target $parent = null;
        private ?array $processing_modules;
        private ?string $processing_template;
        // Relative path below obj/target-chunks, recorded in the saved tree.
        private ?string $processing_chunk = null;
        // Kept on the root so each chunk is read once per loaded tree.
        private array $loaded_chunks = [];

        public function __construct(
            array    $activated_modules,           // dict<string, Module>
            ?string  $template,                    // key of `activated_modules` or `null` if not set
            public readonly ?string $id,
            public readonly ContentLocation $content_location,
            public readonly ?string $login_profile,
            public readonly array   $actions_ser_2_actorlist_ser, // dict<action_serialized, list<actor_serialized>>
            public readonly array   $id_2_child                   // dict<string, Target>
        ) {
            $this->processing_modules = $activated_modules;
            $this->processing_template = $template;
            if ($this->content_location == ContentLocation::INLINE) {
                assert($template !== null, 'Contentful target must have a template');
            }
        }

        public function get_activated_modules(): array {
            $this->load_processing_data();
            return $this->processing_modules;
        }

        public function get_template_key(): ?string {
            $this->load_processing_data();
            return $this->processing_template;
        }

        // Called after construction and validation. Records contain no Target hierarchy references.
        public function detach_processing_data(string $chunk): array {
            $record = [
                'activated_modules' => $this->processing_modules,
                'template' => $this->processing_template,
            ];
            $this->processing_chunk = $chunk;
            $this->processing_modules = null;
            $this->processing_template = null;
            return $record;
        }

        private function load_processing_data(): void {
            if ($this->processing_modules !== null) {
                return;
            }

            $root = $this;
            while ($root->parent !== null) {
                $root = $root->parent;
            }

            $chunk = $this->processing_chunk;
            if (!isset($root->loaded_chunks[$chunk])) {
                $file = path_preprocess('obj/target-chunks/' . $chunk . '/chunk');
                $records = unserialize(file_get_contents($file));
                foreach ($records as $ids_key => $record) {
                    $node = $root->find_child(explode('/', $ids_key));
                    assert($node !== null && $node->processing_chunk === $chunk);
                    $node->processing_modules = $record['activated_modules'];
                    $node->processing_template = $record['template'];
                }
                $root->loaded_chunks[$chunk] = true;
            }
            assert($this->processing_modules !== null, 'Missing target processing data');
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
            return path_collect($this->get_ids(), $suffix);
        }

        public function get_css_slug(): string {
            return implode('_', $this->get_ids());
        }

        public function get_login_profile(): string {
            if ($this->login_profile !== null) {
                return $this->login_profile;
            }

            assert($this->parent !== null, 'Root target has no login profile');
            return $this->parent->get_login_profile();
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

        // Show the current tree state without changing which chunks are loaded.
        public function jsonSerialize(): mixed {
            return [
                'id' => $this->id,
                'content_location' => $this->content_location,
                'login_profile' => $this->login_profile,
                'activated_modules' => $this->processing_modules,
                'template' => $this->processing_template,
                'processing_chunk' => $this->processing_chunk,
                'actions_ser_2_actorlist_ser' => $this->actions_ser_2_actorlist_ser,
                'id_2_child' => $this->id_2_child,
            ];
        }

        public function set_parent(?Target &$parent) {
            $this->parent = $parent;
        }
    }

    class Syslet extends Processable {
        public function __construct(
            public readonly array $activated_modules, // dict<string, Module>
            public readonly string $template,         // key of `activated_modules`, must not be null
        ) {}

        public function get_activated_modules(): array {
            return $this->activated_modules;
        }

        public function get_template_key(): ?string {
            return $this->template;
        }
    }

    final class LoginProfile extends Syslet {
        public function __construct(
            public readonly string $id,
            array $activated_modules,
            string $template,
        ) {
            parent::__construct($activated_modules, $template);
        }
    }
?>
