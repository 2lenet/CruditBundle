<?php

declare(strict_types=1);

namespace phpunit\Unit\Dto\Action;

use Lle\CruditBundle\Dto\Action\AbstractAction;
use Lle\CruditBundle\Dto\Action\ListAction;
use Lle\CruditBundle\Dto\Action\PrintAction;
use Lle\CruditBundle\Dto\Path;
use Lle\CruditUiBundle\Component\ButtonVariant;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The look of an action: a variant of the Button of CruditUiBundle (ButtonVariant, validated), the Bootstrap classes of
 * setCssClass() have no effect (deprecated), the other classes are kept.
 */
class AbstractActionTest extends TestCase
{
    public function testDefaults(): void
    {
        $action = $this->action();

        self::assertSame('secondary', $action->getVariant());
        self::assertFalse($action->isPrimary());
        self::assertNull($action->getGroup());
        self::assertNull($action->getDisabledReason());
        self::assertFalse($action->isDisabled());
        self::assertSame('action.archive', $action->getTitle());
    }

    public function testVariant(): void
    {
        self::assertSame('danger-outline', $this->action()->setVariant('danger-outline')->getVariant());
        self::assertSame('danger', $this->action()->setVariant(ButtonVariant::Danger)->getVariant());
    }

    public function testUnknownVariant(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The variant "btn-primary" of the action "action.archive" is not one of');

        $this->action()->setVariant('btn-primary');
    }

    public function testDisabledReasonIsTheTitle(): void
    {
        $action = $this->action()->setDisabledReason('client.archived');

        self::assertTrue($action->isDisabled());
        self::assertSame('client.archived', $action->getTitle());
    }

    /**
     * @param list<string> $deprecations
     */
    #[DataProvider('cssClasses')]
    public function testCssClass(?string $cssClass, ?string $kept, array $deprecations): void
    {
        $messages = [];
        set_error_handler(static function (int $level, string $message) use (&$messages): bool {
            $messages[] = $message;

            return true;
        }, \E_USER_DEPRECATED);
        try {
            $action = $this->action()->setCssClass($cssClass);
        } finally {
            restore_error_handler();
        }

        self::assertSame($kept, $action->getCssClass());
        self::assertSame($deprecations, $messages);
    }

    /**
     * @return iterable<string, array{?string, ?string, list<string>}>
     */
    public static function cssClasses(): iterable
    {
        $message = 'Since 2lenet/crudit-bundle 2.0: The Bootstrap classes "%s" of the action "action.archive" have no effect, use setVariant().';

        yield 'none' => [null, null, []];
        yield 'own classes' => ['crudit-action js-archive', 'crudit-action js-archive', []];
        yield 'Bootstrap only' => ['btn btn-sm btn-primary', null, [sprintf($message, 'btn btn-sm btn-primary')]];
        yield 'mixed' => ['btn btn-danger  crudit-action', 'crudit-action', [sprintf($message, 'btn btn-danger')]];
        yield 'not a btn' => ['btnx', 'btnx', []];
    }

    public function testPrintAction(): void
    {
        $action = PrintAction::new();

        self::assertSame('crudit.action.print', $action->getLabel());
        self::assertSame('print', $action->getIcon()?->getIcon());
        self::assertNull($action->getPathRole());
        self::assertSame('LleCruditBundle', $action->getTranslationDomain());
        self::assertNull(PrintAction::new('action.print_list')->getTranslationDomain());
    }

    private function action(): AbstractAction
    {
        return ListAction::new('action.archive', Path::new('app_client_archive'));
    }
}
