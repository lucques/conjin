<?
    $default_config = function(): array {
        return [
            'plain'                             => null, // The title in plain text; `null` if not set
            'html'                              => null, // The title in text that may contain HTML; `null` if not set
            'html_short'                        => null, // Short version of `html_short`
            'number_of_dependent_parent_titles' => 0,    // Number of parent titles that this title should be preceded by to make sense
            'is_part_of_content'                => true  // Is this title part of the content or does the content contain it in some other form anyway?
        ];
    }
?>