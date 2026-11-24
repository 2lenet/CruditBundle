<?php

declare(strict_types=1);

namespace phpunit\Unit\Dto\Layout;

use Lle\CruditBundle\Dto\Layout\LinkElement;
use Lle\CruditBundle\Dto\Path;
use PHPUnit\Framework\TestCase;

class LinkElementTest extends TestCase
{
    public function testTheLibelleIsTranslatedInTheDefaultDomainByDefault(): void
    {
        $link = LinkElement::new('menu.test', Path::new('test_index'));

        self::assertNull($link->getTranslationDomain());
    }

    public function testATranslationDomainCanBeSetWithoutChangingTheIdOrTheLibelle(): void
    {
        $link = LinkElement::new('menu.test', Path::new('test_index'))->setTranslationDomain('MyBundle');

        self::assertSame('MyBundle', $link->getTranslationDomain());
        self::assertSame('menu.test', $link->getLibelle());
        self::assertSame('test', $link->getId());
    }
}
