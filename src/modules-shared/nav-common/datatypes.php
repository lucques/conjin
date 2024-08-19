<?
    /*
        A navigation is a tree of `NavItem` objects.
        A `NavItem` is either
        - `TargetNavItem` or
        - `AnchorNavItem` (can only contain `AnchorNavItems` as children)

        All attributes are immutable except for linking together the tree via
        the `children` attribute. This is because the tree is constructed in
        both the preprocessing phase (targets) *and* the processing phase
        (anchors).
        
        Idea for the future: Make immutable and construct the tree completely
        in the preprocessing phase.
    */

    abstract class NavItem {
        // The children are indexed in a map so that access via id is fast  
        protected array $id_2_child;
        // The children are ordered as defined by this list
        protected array $children_ids;
        
        public function __construct(
            public readonly array $target_ids,
            public readonly bool $has_content,
            // Title to use explicitly in navigation
            public readonly string $title,
        ) {
            $this->id_2_child = [];
            $this->children_ids = [];
        }

        public abstract function get_ids(): array;
        public abstract function get_last_id(): ?string;
        public abstract function get_url(): string;
        public abstract function get_css_slug(): string;
            
        public function add_anchor_child(AnchorNavItem $anchorItem): void {
            $this->id_2_child[$anchorItem->get_last_id()] = $anchorItem;
            $this->children_ids[] = $anchorItem->get_last_id();
        }

        public function has_child($id): bool {
            return in_array($id, $this->children_ids);
        }

        public function get_number_of_children(): int {
            return count($this->children_ids);
        }

        public function iterate_children(): ArrayIterator {
            return new ArrayIterator(array_values($this->id_2_child));
        }

        // Recursively find an item in the tree; given a relative path
        public function find(array $target_ids, array $anchor_ids = []): NavItem {
            if (count($target_ids) == 0 && count($anchor_ids) == 0) {
                return $this;
            }
            elseif (count($target_ids) > 0) {
                assert($this->has_child($target_ids[0]));
                return $this->id_2_child[$target_ids[0]]->find(array_slice($target_ids, 1), $anchor_ids);
            }
            else {
                assert($this->has_child($anchor_ids[0]));
                return $this->id_2_child[$anchor_ids[0]]->find([], array_slice($anchor_ids, 1));
            }
        }
    }

    class TargetNavItem extends NavItem {
        public function __construct(
            array $target_ids,
            bool $has_content,
            string $title,
            public readonly array $privileged_actors // list<actor_ser>
        ) {
            parent::__construct($target_ids, $has_content, $title);
        }

        public function get_ids(): array {
            return $this->target_ids;
        }

        public function get_last_id(): ?string {
            if (count($this->target_ids) > 0) {
                return (array_slice($this->target_ids, -1))[0];
            }
            else {
                return null;
            }
        }

        public function get_url(): string {
            return url_collect($this->target_ids);
        }

        public function get_css_slug(): string {
            return implode('_', $this->target_ids);
        }

        public function add_target_child(TargetNavItem $targetItem): void {
            $this->id_2_child[$targetItem->get_last_id()] = $targetItem;
            $this->children_ids[] = $targetItem->get_last_id();
        }
    }

    class AnchorNavItem extends NavItem {
        public function __construct(
            array $target_ids,
            public readonly array $anchor_ids,
            string $title
        ) {
            parent::__construct($target_ids, true, $title);
        }

        public function get_ids(): array {
            return array_merge($this->target_ids, $this->anchor_ids);
        }

        public function get_last_id(): ?string {
            if (count($this->anchor_ids) > 0) {
                return (array_slice($this->anchor_ids, -1))[0];
            }
            else {
                return null;
            }
        }

        public function get_url(): string {
            return url_collect($this->target_ids, $this->anchor_ids);
        }

        public function get_css_slug(): string {
            return implode('_', array_merge($this->target_ids, $this->anchor_ids));
        }
    }
?>