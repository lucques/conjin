<?
    $init_preprocessing_target = function(Module $m, TargetPreprocessContext $c) {
        
        $path = path_collect($c->target_ids);
        $items = scandir($path);

        // Loop through the items
        foreach ($items as $item) {
            // Ignore the special directories "." and ".."
            if ($item != "." && $item != "..") {
                // Check if the item is a directory but not called 'res`
                if (is_dir($path . '/' . $item) && $item != 'res') {
                    $c->add_subpage($item);
                }
            }
        }
    };
?>