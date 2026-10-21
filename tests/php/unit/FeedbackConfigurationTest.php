<?
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use Conjin\Modules\FeedbackCollector as F;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
#[\PHPUnit\Framework\Attributes\Group('module:feedback-collector')]
final class FeedbackConfigurationTest extends TestCase {
    protected function setUp(): void {
        require __DIR__ . '/../fixtures/feedback.php';
    }

    public static function invalidConfigurations(): array {
        return ['blank question' => [0], 'blank option' => [1], 'blank section' => [2], 'invalid section member' => [3]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidConfigurations')]
    public function testRejectsInvalidConfiguration(int $kind): void {
        $element = match ($kind) {
            0 => new F\Question(' ', new F\Text()),
            1 => new F\Question('Choice', new F\SingleChoice([new F\Option('')])),
            2 => new F\Section(' ', []),
            3 => new F\Section('Invalid content', [new stdClass()]),
        };
        $this->expectException(InvalidArgumentException::class);
        new F\FormConfig('Invalid configuration', [$element]);
    }
}
