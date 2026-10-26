<?php

declare(strict_types=1);

namespace phpunit\Unit\Service;

use Lle\CruditBundle\Service\EasyAdminConverter\Converter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The Bootstrap classes of an EasyAdmin action: btn-primary is the primary action (setPrimary()), the others a variant
 * of Button (setVariant()), the other classes kept.
 */
class EasyAdminConverterTest extends TestCase
{
    /**
     * @return iterable<string, array{?string, ?string, bool, ?string}>
     */
    public static function classes(): iterable
    {
        yield 'none' => [null, null, false, null];
        yield 'primary' => ['btn btn-sm btn-primary', null, true, null];
        yield 'danger' => ['btn btn-danger js-delete', 'danger', false, 'js-delete'];
        yield 'outline danger' => ['btn btn-outline-danger', 'danger-outline', false, null];
        yield 'outline primary' => ['btn btn-outline-primary', 'secondary', false, null];
        yield 'link' => ['btn-link', 'link', false, null];
        yield 'unknown' => ['btn-info', null, false, null];
    }

    #[DataProvider('classes')]
    public function testVariantOfAnAction(?string $cssClass, ?string $variant, bool $primary, ?string $kept): void
    {
        $converter = (new \ReflectionClass(Converter::class))->newInstanceWithoutConstructor();
        $action = (new \ReflectionMethod(Converter::class, 'getPropertyAction'))->invoke($converter, ['name' => 'app_x', 'css_class' => $cssClass]);

        self::assertSame([$variant, $primary, $kept], [$action['variant'], $action['primary'], $action['cssClass']]);
    }
}
