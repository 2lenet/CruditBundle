<?php

declare(strict_types=1);

namespace phpunit\Unit\Icon;

use Lle\CruditBundle\Contracts\MenuProviderInterface;
use Lle\CruditBundle\Dto\Icon;
use Lle\CruditBundle\Dto\Layout\ExternalLinkElement;
use Lle\CruditBundle\Dto\Layout\LinkElement;
use Lle\CruditBundle\Dto\Layout\TitleElement;
use Lle\CruditBundle\Dto\Path;
use Lle\CruditBundle\Icon\MenuIconFinder;
use Lle\CruditBundle\Registry\MenuRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;

/**
 * The Iconify names of the menu, children included, for ux:icons:lock.
 */
class MenuIconFinderTest extends TestCase
{
    public function testIcons(): void
    {
        $billing = LinkElement::new('billing', null, Icon::new('fa7-solid:euro-sign'));
        $billing->add(LinkElement::new('invoices', Path::new('invoices'), Icon::new('lucide:file-text')));
        $title = TitleElement::new('administration');
        $title->addChild(LinkElement::new('users', Path::new('users'), Icon::new('fa7-solid:users')));

        $provider = $this->provider(static fn (): array => [
            LinkElement::new('home', Path::new('home'), Icon::new('home')),
            LinkElement::new('clients', Path::new('clients'), Icon::new('fa7-solid:users')),
            ExternalLinkElement::new('doc', 'https://example.org', Icon::new('fa7-solid:book')),
            LinkElement::new('none', Path::new('none')),
            $billing,
            $title,
        ]);

        self::assertSame(
            ['fa7-solid:users', 'fa7-solid:book', 'fa7-solid:euro-sign', 'lucide:file-text'],
            (new MenuIconFinder(new MenuRegistry([$provider])))->icons(),
        );
    }

    public function testFailingProviderIsLogged(): void
    {
        $provider = $this->provider(static fn (): array => throw new \LogicException('No user'));
        $logger = new class () extends AbstractLogger {
            /** @var list<string> */
            public array $messages = [];

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->messages[] = $level . ': ' . strtr((string) $message, ['{message}' => $context['message']]);
            }
        };

        self::assertSame([], (new MenuIconFinder(new MenuRegistry([$provider]), $logger))->icons());
        self::assertSame(['warning: The icons of the menu cannot be found: No user'], $logger->messages);
    }

    /** @param \Closure(): array<mixed> $entries */
    private function provider(\Closure $entries): MenuProviderInterface
    {
        return new class ($entries) implements MenuProviderInterface {
            public function __construct(private \Closure $entries)
            {
            }

            public function getMenuEntry(): iterable
            {
                return ($this->entries)();
            }
        };
    }
}
