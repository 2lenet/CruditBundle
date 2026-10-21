<?php

declare(strict_types=1);

namespace phpunit\Unit\Twig;

use Lle\CruditBundle\Twig\SidebarExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The state of the menu is read from the cookie crudit_sidebar; anything else is `auto`.
 */
class SidebarExtensionTest extends TestCase
{
    /**
     * @return iterable<string, array{string|null, string}>
     */
    public static function cookies(): iterable
    {
        yield 'no cookie' => [null, 'auto'];
        yield 'auto' => ['auto', 'auto'];
        yield 'collapsed' => ['collapsed', 'collapsed'];
        yield 'expanded' => ['expanded', 'expanded'];
        yield 'unknown value' => ['<script>', 'auto'];
        yield 'empty' => ['', 'auto'];
    }

    #[DataProvider('cookies')]
    public function testSidebar(?string $cookie, string $expected): void
    {
        $requests = new RequestStack();
        $requests->push(new Request(cookies: null === $cookie ? [] : [SidebarExtension::COOKIE => $cookie]));

        self::assertSame($expected, (new SidebarExtension($requests))->sidebar());
    }

    public function testNoRequest(): void
    {
        self::assertSame('auto', (new SidebarExtension(new RequestStack()))->sidebar());
    }

    public function testFunctionIsRegistered(): void
    {
        $names = array_map(static fn ($function) => $function->getName(), (new SidebarExtension(new RequestStack()))->getFunctions());

        self::assertSame(['crudit_sidebar'], $names);
    }
}
