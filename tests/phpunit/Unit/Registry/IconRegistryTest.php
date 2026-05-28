<?php

declare(strict_types=1);

namespace phpunit\Unit\Registry;

use Lle\CruditBundle\Registry\IconRegistry;
use PHPUnit\Framework\TestCase;

class IconRegistryTest extends TestCase
{
    public function testGetReturnsConfiguredValue(): void
    {
        $registry = new IconRegistry(['edit' => 'bi bi-pencil']);

        self::assertSame('bi bi-pencil', $registry->get('edit'));
        self::assertTrue($registry->has('edit'));
    }

    public function testGetReturnsNameWhenKeyUnknown(): void
    {
        $registry = new IconRegistry([]);

        self::assertSame('unknown', $registry->get('unknown'));
        self::assertFalse($registry->has('unknown'));
    }

    public function testAllReturnsFullMap(): void
    {
        $map = ['edit' => 'bi bi-pencil', 'delete' => 'bi bi-trash'];
        $registry = new IconRegistry($map);

        self::assertSame($map, $registry->all());
    }
}
