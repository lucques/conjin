<?
    namespace Conjin\Modules\Nuggets;

use Conjin\Modules\Nuggets\Blog\BlogPostNugget;
use Conjin\Modules\Nuggets\Exercise\ExerciseNugget;

    
    readonly class GenericPrinterConfig extends PrinterConfig {}


    class GenericPrinter extends Printer {
        function __construct(GenericPrinterConfig $config) {
            parent::__construct($config);
        }

        static function make_default() {
            return new GenericPrinter(
                new GenericPrinterConfig(
                    target_ids: ['nuggets'],
                )
            );
        }
    }
?>