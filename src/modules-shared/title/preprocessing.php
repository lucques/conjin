<?
    $set = function(
        PreprocessContext $c,
        ?string $plain = null,
        ?string $plain_short = null,  // Does not exist as config but is used as fallback for `html_short`
        ?string $html = null,
        ?string $html_short = null,
        int $number_of_dependent_parent_titles = 0,
        bool $is_part_of_content = true
    ) {
        assert($html_short === null || $plain_short === null, 'Cannot set both plain_short and html_short at the same time');

        $c->update_module_config('title', [
            'plain'                             => $plain,
            'html'                              => $html,
            'html_short'                        => $html_short ?? $plain_short,
            'number_of_dependent_parent_titles' => $number_of_dependent_parent_titles,
            'is_part_of_content'                => $is_part_of_content
        ]);
    };
?>