<?php

declare(strict_types=1);

namespace phpunit\Unit\Brick;

use Lle\CruditBundle\Action\PageActionsBuilder;
use Lle\CruditBundle\Brick\LinksBrick\LinksConfig;
use Lle\CruditBundle\Brick\LinksBrick\LinksFactory;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Dto\Action\ItemAction;
use Lle\CruditBundle\Dto\Path;
use Lle\CruditBundle\Resolver\ResourceResolver;
use Lle\CruditBundle\Service\NavigationStack;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The way back to the list (setBack()) goes back by the history of the navigation; one of 1.x, recognised by its label
 * action.list, is still one during 2.x, with a deprecation.
 */
class LinksFactoryTest extends TestCase
{
    public function testTheWayBackGoesBackByTheHistory(): void
    {
        $back = ItemAction::new('action.back_to_clients', Path::new('app_client_index'))->setBack();
        $other = ItemAction::new('action.other', Path::new('app_client_other'));

        $this->factory()->getActions((new LinksConfig())->addAction($back)->addAction($other));

        self::assertSame('/client/?page=2', $back->getUrl());
        self::assertNull($other->getUrl());
    }

    public function testTheWayBackOf1xByItsLabel(): void
    {
        $back = ItemAction::new(CrudConfigInterface::LABEL_ACTION_LIST, Path::new('app_client_index'));

        $messages = [];
        set_error_handler(static function (int $level, string $message) use (&$messages): bool {
            $messages[] = $message;

            return true;
        }, \E_USER_DEPRECATED);
        try {
            $this->factory()->getActions((new LinksConfig())->addAction($back));
        } finally {
            restore_error_handler();
        }

        self::assertSame(['Since 2lenet/crudit-bundle 2.0: The action "action.list" is recognised as the way back to the list by its label, add ->setBack() to it.'], $messages);
        self::assertTrue($back->isBack());
        self::assertSame('/client/?page=2', $back->getUrl());
    }

    private function factory(): LinksFactory
    {
        $navigation = $this->createStub(NavigationStack::class);
        $navigation->method('peek')->willReturn('/client/?page=2');

        return new LinksFactory(
            $this->createStub(ResourceResolver::class),
            new RequestStack(),
            $navigation,
            $this->createStub(PageActionsBuilder::class),
        );
    }
}
