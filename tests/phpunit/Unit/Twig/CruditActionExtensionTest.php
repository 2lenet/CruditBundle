<?php

declare(strict_types=1);

namespace phpunit\Unit\Twig;

use Lle\CruditBundle\Action\ActionResolver;
use Lle\CruditBundle\Action\PageActionsBuilder;
use Lle\CruditBundle\Dto\Action\DeleteAction;
use Lle\CruditBundle\Dto\Action\ListAction;
use Lle\CruditBundle\Dto\Path;
use Lle\CruditBundle\Twig\CruditActionExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The attributes of the element of an action: the confirmation names the element of its line, even when all the
 * lines are built before being rendered (one instance of the action for all the lines).
 */
class CruditActionExtensionTest extends TestCase
{
    public function testConfirmationNamesItsOwnLine(): void
    {
        $delete = DeleteAction::new('action.delete', Path::new('app_client_delete'))->setConfirmModal(true)->setVariant('danger');
        $builder = $this->builder();
        $alice = $builder->buildRow([$delete], $this->client('Alice'), 1);
        $bob = $builder->buildRow([$delete], $this->client('Bob'), 2);
        $extension = new CruditActionExtension($this->translator());

        // Built after the line of Bob, rendered after it: the line of Alice still names Alice
        $attributes = $extension->actionAttributes($alice->others[0]);
        self::assertSame('Delete “Alice”?', $attributes['data-confirm-text']);
        self::assertSame('Delete', $attributes['data-confirm-label']);
        self::assertSame('/app_client_delete?id=1', $attributes['data-confirm-link']);
        self::assertSame('true', $attributes['data-confirm-danger']);
        self::assertSame('Delete “Bob”?', $extension->actionAttributes($bob->others[0])['data-confirm-text']);
    }

    public function testDisabledAction(): void
    {
        $delete = DeleteAction::new('action.delete', Path::new('app_client_delete'))->setConfirmModal(true)->setCssClass('js-delete');
        $view = $this->builder()->buildRow([$delete], $this->client('Alice', 'client.active'), 1)->others[0];
        $extension = new CruditActionExtension();

        self::assertNull($extension->actionHref($view));
        self::assertSame(['class' => 'js-delete', 'aria-disabled' => 'true', 'aria-describedby' => 'reason'], $extension->actionAttributes($view, null, 'reason'));
    }

    public function testTheWayBackAndAnElementWithoutString(): void
    {
        $extension = new CruditActionExtension($this->translator());
        $back = ListAction::new('action.back_to_clients', Path::new('app_client_index'))->setBack();
        $delete = DeleteAction::new('action.delete', Path::new('app_client_delete'))->setConfirmModal(true);

        self::assertSame('noreferrer', $extension->actionAttributes($this->builder()->build([$back])->inline[0])['rel']);
        self::assertSame('Delete “#4”?', $extension->actionAttributes($this->builder()->buildRow([$delete], new \stdClass(), 4)->others[0])['data-confirm-text']);
    }

    private function translator(): TranslatorInterface
    {
        return new class () implements TranslatorInterface {
            public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
            {
                $messages = ['crudit.modal.confirm.question_entity' => '%action% “%entity%”?', 'action.delete' => 'Delete'];

                return strtr($messages[$id] ?? $id, $parameters);
            }

            public function getLocale(): string
            {
                return 'en';
            }
        };
    }

    private function client(string $name, bool|string $canDelete = true): object
    {
        return new class ($name, $canDelete) implements \Stringable {
            public function __construct(private string $name, private bool|string $canDelete)
            {
            }

            public function canDelete(): bool|string
            {
                return $this->canDelete;
            }

            public function __toString(): string
            {
                return $this->name;
            }
        };
    }

    private function builder(): PageActionsBuilder
    {
        $checker = new class () implements AuthorizationCheckerInterface {
            public function isGranted(mixed $attribute, mixed $subject = null, mixed $accessDecision = null): bool
            {
                return true;
            }
        };
        $urls = new class () implements UrlGeneratorInterface {
            public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string
            {
                return '/' . $name . ($parameters ? '?' . http_build_query($parameters) : '');
            }

            public function setContext(RequestContext $context): void
            {
            }

            public function getContext(): RequestContext
            {
                return new RequestContext();
            }
        };
        $stack = new RequestStack();
        $stack->push(new Request());

        return new PageActionsBuilder(new ActionResolver($checker), $urls, $stack);
    }
}
