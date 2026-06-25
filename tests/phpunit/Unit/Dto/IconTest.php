<?php

declare(strict_types=1);

namespace phpunit\Unit\Dto;

use Lle\CruditBundle\Dto\Icon;
use PHPUnit\Framework\TestCase;

class IconTest extends TestCase
{
    public function testNewReturnsFontAwesomeCssClass(): void
    {
        $icon = Icon::new('edit');

        self::assertSame(Icon::TYPE_FA, $icon->getType());
        self::assertSame('fa fa-edit', $icon->getCssClass());
        self::assertFalse($icon->isLogical());
        self::assertFalse($icon->isImg());
    }

    public function testLogicalIconCarriesKeyAndReturnsEmptyCssClass(): void
    {
        $icon = Icon::logical('delete');

        self::assertTrue($icon->isLogical());
        self::assertSame('delete', $icon->getIcon());
        self::assertSame(Icon::TYPE_LOGICAL, $icon->getType());
        // getCssClass() must not produce a FA class for logical icons —
        // the resolution happens through crudit_icon() in templates.
        self::assertSame('', $icon->getCssClass());
    }

    public function testImgIconReturnsEmptyCssClass(): void
    {
        $icon = Icon::new('/img/foo.png', Icon::TYPE_IMG);

        self::assertTrue($icon->isImg());
        self::assertSame('', $icon->getCssClass());
    }

    public function testCustomTypeRequiresPrefix(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Icon::new('foo', 'bi');
    }

    public function testCustomTypeWithPrefix(): void
    {
        $icon = Icon::new('search', 'bi', 'bi');

        self::assertSame('bi bi-search', $icon->getCssClass());
    }
}
