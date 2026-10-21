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

        // The header is reworked in lot 2.2.a; the menu, the logo, the footer and the modals are done.
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

        // One of each kind of heading (two titles: alone and with children), the separator is hidden from assistive
        // technologies. Removed (MenuBuilder): the link the user is not granted, in a title or alone, the category it
        // leaves alone, the separator at the start and the doubled one.
        self::assertSame(['Operations'], $crawler->filter('.crudit-nav__heading--category')->each(static fn ($h) => trim($h->text())));
        self::assertSame(['Administration', 'Tools'], $crawler->filter('.crudit-nav__heading--title')->each(static fn ($h) => trim($h->text())));
        self::assertCount(1, $crawler->filter('.crudit-nav__heading--header'));
        self::assertCount(1, $crawler->filter('.crudit-nav__separator[aria-hidden="true"]'));
        self::assertSame('item', $crawler->filter('.crudit-nav > li')->first()->attr('data-menu-kind'), 'no separator at the start');
        self::assertCount(0, $crawler->filter('[data-menu-label="Secret"]'));
        // The children of a title follow it in the same list
        self::assertSame('Exports', $crawler->filterXPath('//li[span="Tools"]/following-sibling::li[1]')->attr('data-menu-label'));

        // Icons: SVG of Symfony UX Icons (Iconify name or logical name), the first letter of the label without icon
        self::assertCount(1, $crawler->filter('[data-menu-label="Clients"] .crudit-nav__icon svg'));
        self::assertCount(1, $crawler->filter('[data-menu-label="Users"] .crudit-nav__icon svg'));
        self::assertSame('R', trim($crawler->filter('[data-menu-label="Reports"] .crudit-nav__icon')->text()));

        // Badge: its tone, its text for assistive technologies, a dot for the rail, shown in the tooltip
        $badge = $crawler->filter('[data-menu-label="Clients"] .crudit-nav__badge');
        self::assertStringContainsString('crudit-ui-badge crudit-ui-tone--danger', (string) $badge->attr('class'));
        self::assertSame('12 clients to call', trim(str_replace("\u{a0}", ' ', $badge->text())));
        self::assertSame('clients to call', trim(str_replace("\u{a0}", ' ', $badge->filter('.crudit-ui-visually-hidden')->text())));
        self::assertCount(1, $crawler->filter('[data-menu-label="Clients"] .crudit-nav__dot.crudit-ui-tone--danger[aria-hidden="true"]'));
        self::assertSame('Clients · 12', $crawler->filter('[data-menu-label="Clients"] .crudit-ui-tooltip')->text());

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

        // One level of sub-menu: the children of a link of a sub-menu are not rendered
        self::assertCount(2, $crawler->filter('.crudit-nav__submenu'));
        self::assertCount(0, $crawler->filter('[data-menu-label="Late reminders"]'));

        // New tab, in a sub-menu (a link with a target) as at the first level (an external link): icon, announced, safe rel
        foreach (['Map', 'Documentation'] as $label) {
            $link = $crawler->filter(sprintf('[data-menu-label="%s"] a', $label));
            self::assertSame('_blank', $link->attr('target'), $label);
            self::assertSame('noopener noreferrer', $link->attr('rel'), $label);
            self::assertStringContainsString('(new tab)', $link->text(), $label);
            self::assertCount(1, $link->filter('svg.crudit-nav__external'), $label);
        }

        // Tooltip of the rail on the items of the first level, not on the links of a sub-menu
        self::assertCount(1, $crawler->filter('[data-menu-label="Home"] .crudit-ui-tooltip'));
        self::assertCount(0, $crawler->filter('[data-menu-label="Invoices"] .crudit-ui-tooltip'));
        self::assertCount(0, $crawler->filter('[data-menu-label="Map"] .crudit-ui-tooltip'));

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
    /**
     * Logo, favicon and footer from lle_crudit.layout (config/packages/lle_crudit.yaml).
     */
    public function testLogoFaviconAndFooter(): void
    {
        $crawler = $this->client->request('GET', '/page/invoices');

        // Favicon of the configuration (AssetMapper), no logo.png left
        self::assertMatchesRegularExpression('#^/assets/images/favicon-[\w-]+\.svg$#', (string) $crawler->filter('link[rel="icon"]')->attr('href'));
        self::assertStringNotContainsString('logo.png', (string) $this->client->getResponse()->getContent());

        // Link of the logo: named "<application>, home", decorative images, one per theme, the initial in the rail
        $brand = $crawler->filter('#crudit-sidebar a.crudit-brand');
        self::assertSame('/', $brand->attr('href'));
        self::assertSame('Gestio, home', trim($brand->filter('.crudit-ui-visually-hidden')->text()));
        self::assertCount(1, $brand->filter('.crudit-brand__full--themed'));
        self::assertSame(['', ''], $brand->filter('img.crudit-brand__logo')->each(static fn ($img) => $img->attr('alt')));
        self::assertMatchesRegularExpression('#^/assets/images/logo-[\w-]+\.svg$#', (string) $brand->filter('.crudit-brand__logo--light')->attr('src'));
        self::assertMatchesRegularExpression('#^/assets/images/logo-dark-[\w-]+\.svg$#', (string) $brand->filter('.crudit-brand__logo--dark')->attr('src'));
        self::assertSame('G', trim($brand->filter('span.crudit-brand__mark[aria-hidden="true"]')->text()));

        // Footer: the links of the configuration (a route, an address), the version and its date
        $nav = $crawler->filter('footer.crudit-footer nav[aria-label="Legal information and help"]');
        self::assertSame(['/page/accessibility', 'https://example.org/legal'], $nav->filter('a')->each(static fn ($a) => $a->attr('href')));
        self::assertSame(['Accessibility statement', 'Legal notice'], $nav->filter('a')->each(static fn ($a) => $a->text()));
        $version = $crawler->filter('.crudit-footer__version');
        self::assertSame('Release test · updated on October 21, 2026', $version->text());
        self::assertSame('2026-10-21', $version->filter('time')->attr('datetime'));
    }

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
