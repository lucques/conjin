<?
    namespace Conjin\Modules\Nuggets\Blog;

    use Conjin\Modules\Nuggets\Raw\RawNugget;
    use DateTime;

    ////////////////
    // Data types //
    ////////////////

    readonly class BlogPostNugget extends RawNugget {
        public function __construct(
            string $content_id,
            public string $title,
            public DateTime $date,
            public array $target_ids_canonical,  // The target IDs that lead to the canonical web page for the blog post

            ?array $config_default = null
        ) {
            parent::__construct(
                content_id: $content_id,
                config_default: $config_default
            );
        }

        public static function make_blog_post(
            string $content_id,
            string $title,
            string $date,
            array $target_ids_canonical,
        ): self {
            return new self(
                content_id: $content_id,
                title: $title,
                date: DateTime::createFromFormat('Y-m-d', $date),
                target_ids_canonical: $target_ids_canonical,
            );
        }
    }
?>