<?
    namespace Conjin\Modules\Nuggets\Raw;

    use Conjin\Modules\Nuggets\LeafNugget;


    ////////////////
    // Data types //
    ////////////////

    readonly class RawNugget extends LeafNugget {
        public function __construct(
            public string $content_id,
            ?array $config_default = null
        ) {
            parent::__construct(config_default: $config_default);
        }

        static function make_raw($content_id = 'main', ?array $config_default = null): self {
            return new self(content_id: $content_id, config_default: $config_default);
        }
    }
?>