<?php

declare(strict_types=1);

namespace phpunit\Unit\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Lle\CruditBundle\Dto\Icon;
use Lle\CruditBundle\Registry\IconRegistry;
use Lle\CruditBundle\Registry\MenuRegistry;
use Lle\CruditBundle\Twig\CruditExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\Registry;

class CruditExtensionIconTest extends TestCase
{
    private function makeExtension(array $icons): CruditExtension
    {
        return new CruditExtension(
            $this->createMock(MenuRegistry::class),
            $this->createMock(RouterInterface::class),
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(ParameterBagInterface::class),
            $this->createMock(Registry::class),
            new IconRegistry($icons),
        );
    }

    public function testGetIconResolvesStringKeyThroughRegistry(): void
    {
        $ext = $this->makeExtension(['edit' => 'bi bi-pencil']);

        self::assertSame('bi bi-pencil', $ext->getIcon('edit'));
    }

    public function testGetIconResolvesLogicalIconThroughRegistry(): void
    {
        $ext = $this->makeExtension(['edit' => 'bi bi-pencil']);

        self::assertSame('bi bi-pencil', $ext->getIcon(Icon::logical('edit')));
    }

    public function testGetIconReturnsCssClassForFontAwesomeIcon(): void
    {
        // A plain Icon::new('edit') must still render its FA cssClass
        // so that apps not migrated to logical keys keep working.
        $ext = $this->makeExtension(['edit' => 'bi bi-pencil']);

        self::assertSame('fa fa-edit', $ext->getIcon(Icon::new('edit')));
    }

    public function testGetIconReturnsEmptyStringForImgIcon(): void
    {
        $ext = $this->makeExtension([]);

        self::assertSame('', $ext->getIcon(Icon::new('/img/foo.png', Icon::TYPE_IMG)));
    }

    public function testGetIconReturnsEmptyStringForNull(): void
    {
        $ext = $this->makeExtension([]);

        self::assertSame('', $ext->getIcon(null));
    }

    public function testGetIconReturnsNameWhenKeyUnknown(): void
    {
        $ext = $this->makeExtension([]);

        // Falls through to IconRegistry::get(), which returns the key untouched
        // when missing (preserves whatever the caller passed).
        self::assertSame('search', $ext->getIcon('search'));
    }
}
