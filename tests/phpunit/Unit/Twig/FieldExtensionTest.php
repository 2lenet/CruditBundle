<?php

declare(strict_types=1);

namespace phpunit\Unit\Twig;

use Doctrine\Common\Collections\ArrayCollection;
use Lle\CruditBundle\Twig\FieldExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * `value is crudit_empty`: what the templates of the fields show as a dash read as "Not set".
 */
class FieldExtensionTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function values(): iterable
    {
        yield 'null' => [null, true];
        yield 'empty string' => ['', true];
        yield 'empty array' => [[], true];
        yield 'empty collection' => [new ArrayCollection(), true];
        yield 'empty iterator' => [new \ArrayIterator([]), true];
        yield 'false is a value' => [false, false];
        yield '0 is a value' => [0, false];
        yield "'0' is a value" => ['0', false];
        yield 'a space is a value' => [' ', false];
        yield 'an array' => [[[]], false];
        yield 'a collection' => [new ArrayCollection([1]), false];
        yield 'an object' => [new \stdClass(), false];
    }

    #[DataProvider('values')]
    public function testEmpty(mixed $value, bool $empty): void
    {
        self::assertSame($empty, FieldExtension::isEmpty($value));
    }
}
