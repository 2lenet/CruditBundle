<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\BrowserKit\Cookie;

/**
 * The layout of Crudit (layout/base.html.twig): landmarks, skip links, the menu and its state.
 */
class LayoutTest extends AppTestCase
{
    public function testLandmarksAndSkipLinks(): void
    {
        $crawler = $this->client->request('GET', '/page/invoices');

        self::assertResponseIsSuccessful();
        self::assertSame('en', $crawler->filter('html')->attr('lang'));
        self::assertSame('ltr', $crawler->filter('html')->attr('dir'));
        self::assertCount(1, $crawler->filter('h1'));
        self::assertCount(1, $crawler->filter('main#main[tabindex="-1"]'));
        self::assertCount(1, $crawler->filter('nav#main-nav[aria-label="Main menu"]'));
        self::assertCount(1, $crawler->filter('#crudit-sidebar[role="complementary"]'));
        self::assertSame(['#main', '#main-nav'], $crawler->filter('.crudit-skip-links a')->each(static fn ($a) => $a->attr('href')));
        self::assertCount(1, $crawler->filter('.crudit-shell__header'));
        self::assertCount(1, $crawler->filter('footer.crudit-footer'));
        self::assertCount(0, $crawler->filter('.crudit-footer__copyright'));
        self::assertSame('Invoices – Gestio', $crawler->filter('title')->text());
    }

    public function testNoBootstrapNorSbAdminLeft(): void
    {
        $crawler = $this->client->request('GET', '/page/invoices');

        // The header, the logo and the footer are reworked in lots 2.2.a, 2.1.c; the menu and the modals are done.
        self::assertCount(0, $crawler->filter('#sidebar-collapser, #crudit-sidebar .nav-link, #crudit-sidebar .btn-toggle, #crudit-sidebar [data-bs-toggle], .modal, .modal-dialog'));
        self::assertStringNotContainsString('sbadmin', (string) $this->client->getResponse()->getContent());
    }

    /**
     * @return iterable<string, array{string|null, string}>
     */
    public static function sidebarCookies(): iterable
    {
        yield 'no cookie' => [null, 'auto'];
        yield 'collapsed' => ['collapsed', 'collapsed'];
        yield 'expanded' => ['expanded', 'expanded'];
        yield 'garbage' => ['x"><script>', 'auto'];
    }

    #[DataProvider('sidebarCookies')]
    public function testSidebarStateIsReadFromTheCookie(?string $cookie, string $expected): void
    {
        if (null !== $cookie) {
            $this->client->getCookieJar()->set(new Cookie('crudit_sidebar', $cookie));
        }
        $crawler = $this->client->request('GET', '/');

        self::assertSame($expected, $crawler->filter('.crudit-shell')->attr('data-sidebar'));
        self::assertCount(1, $crawler->filter('[data-controller="2lenet--crudit-bundle--sidebar"]'));
    }

    public function testMenuMarkup(): void
    {
        $crawler = $this->client->request('GET', '/page/invoices');

        // Items, with the label the filter reads
        $labels = $crawler->filter('#main-nav [data-menu-kind="item"]')->each(static fn ($item) => $item->attr('data-menu-label'));
        self::assertContains('Home', $labels);
        self::assertContains('Billing', $labels);
        self::assertContains('Invoices', $labels);
        self::assertContains('Documentation', $labels);

        // One of each kind of heading, and the separator is hidden from assistive technologies
        self::assertCount(1, $crawler->filter('.crudit-nav__heading--category'));
        self::assertCount(1, $crawler->filter('.crudit-nav__heading--title'));
        self::assertCount(1, $crawler->filter('.crudit-nav__heading--header'));
        self::assertCount(1, $crawler->filter('.crudit-nav__separator[aria-hidden="true"]'));

        // Current page: aria-current on its link, its sub-menu open, the other one closed
        self::assertSame('Invoices', trim($crawler->filter('#main-nav [aria-current="page"] .crudit-nav__label')->text()));
        self::assertCount(1, $crawler->filter('#main-nav [aria-current="page"]'));
        self::assertSame('true', $crawler->filter('[data-menu-label="Billing"] button')->attr('aria-expanded'));
        self::assertSame('false', $crawler->filter('[data-menu-label="Stock"] button')->attr('aria-expanded'));
        $panel = $crawler->filter('[data-menu-label="Billing"] button')->attr('aria-controls');
        self::assertCount(1, $crawler->filter('#' . $panel));
        self::assertStringContainsString('crudit-ui-disclosure__panel--closed', (string) $crawler->filter('[data-menu-label="Stock"] .crudit-nav__submenu')->attr('class'));

        // External link: new tab announced, safe rel
        $external = $crawler->filter('[data-menu-label="Documentation"] a');
        self::assertSame('_blank', $external->attr('target'));
        self::assertSame('noopener noreferrer', $external->attr('rel'));
        self::assertStringContainsString('(new tab)', $external->text());

        // Tooltip of the rail on the items of the first level, not on the links of a sub-menu
        self::assertCount(1, $crawler->filter('[data-menu-label="Home"] .crudit-ui-tooltip'));
        self::assertCount(0, $crawler->filter('[data-menu-label="Invoices"] .crudit-ui-tooltip'));

        // Every id is unique
        $ids = $crawler->filter('[id]')->each(static fn ($node) => $node->attr('id'));
        self::assertSame(array_unique($ids), $ids);
    }

    public function testMenuOnACrudPage(): void
    {
        $crawler = $this->client->request('GET', '/client/');

        self::assertResponseIsSuccessful();
        self::assertSame('Clients', trim($crawler->filter('#main-nav [aria-current="page"] .crudit-nav__label')->text()));
    }

    public function testDrawerAndFilterControls(): void
    {
        $crawler = $this->client->request('GET', '/');

        self::assertSame('crudit-sidebar', $crawler->filter('.crudit-header__burger')->attr('aria-controls'));
        self::assertSame('false', $crawler->filter('.crudit-header__burger')->attr('aria-expanded'));
        self::assertSame('Open the menu', trim($crawler->filter('.crudit-header__burger .crudit-ui-visually-hidden')->text()));
        self::assertSame('Close the menu', trim($crawler->filter('.crudit-sidebar__close .crudit-ui-visually-hidden')->text()));
        self::assertCount(1, $crawler->filter('[role="search"] label[for="crudit-menu-filter"]'));
        self::assertCount(1, $crawler->filter('input#crudit-menu-filter[type="search"][aria-describedby="crudit-menu-filter-status"]'));
        self::assertCount(1, $crawler->filter('#crudit-menu-filter-status[aria-live="polite"]'));
        self::assertSame('Collapse the menu', trim($crawler->filter('.crudit-sidebar__collapse-label')->text()));
        self::assertCount(1, $crawler->filter('.crudit-shell__backdrop[hidden]'));
    }

    /**
     * The blocks that 1.x kept in included templates (where they could not be overridden) are in the layout.
     */
    public function testEveryBlockCanBeOverridden(): void
    {
        $crawler = $this->client->request('GET', '/overrides');

        self::assertResponseIsSuccessful();
        self::assertSame('MY LOGO', $crawler->filter('.crudit-sidebar__brand #o-logo')->text());
        self::assertCount(1, $crawler->filter('#main-nav #o-side-header'));
        self::assertCount(1, $crawler->filter('#main-nav #o-side-footer'));
        self::assertCount(1, $crawler->filter('.crudit-header #o-header-nav'));
        self::assertCount(1, $crawler->filter('.crudit-header #o-header-right'));
        self::assertCount(0, $crawler->filter('.crudit-header .navbar-nav'));
        self::assertCount(0, $crawler->filter('#crudit-menu-filter, .crudit-sidebar__collapse'));
        self::assertCount(1, $crawler->filter('footer#o-footer'));
        self::assertCount(0, $crawler->filter('.crudit-footer'));
    }
}
