<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Security\UserProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\BrowserKit\Cookie;

/**
 * The header (lot 2.2.a): the logo below 1024 px, the user menu (CruditUserInterface or the name only), the logout in
 * POST with its CSRF token, the appearance, the banner of the impersonation.
 */
class HeaderTest extends AppTestCase
{
    public function testNoUserMenuWithoutUser(): void
    {
        $crawler = $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('#crudit-user-menu'));
        self::assertCount(0, $crawler->filter('.crudit-impersonation'));
    }

    public function testLogoOfTheMenuInTheHeader(): void
    {
        $crawler = $this->client->request('GET', '/');

        $brand = $crawler->filter('.crudit-header .crudit-header__brand a.crudit-brand');
        self::assertSame('/', $brand->attr('href'));
        self::assertSame('Gestio, home', trim($brand->filter('.crudit-ui-visually-hidden')->text()));
    }

    public function testUserMenuOfACruditUser(): void
    {
        $this->login('alice');
        $crawler = $this->client->request('GET', '/');

        $menu = $crawler->filter('.crudit-header__end #crudit-user-menu');
        self::assertCount(1, $menu);
        $button = $menu->filter('button.crudit-header__user');
        self::assertSame('false', $button->attr('aria-expanded'));
        self::assertSame('crudit-user-menu-panel', $button->attr('aria-controls'));
        self::assertSame('AM', trim($button->filter('.crudit-ui-avatar')->text()));
        self::assertSame('Alice Martin', $button->filter('.crudit-header__user-name')->text());
        self::assertSame('Administrator', $button->filter('.crudit-header__user-role')->text());

        $card = $menu->filter('#crudit-user-menu-panel .crudit-header__user-card');
        self::assertSame('Alice Martin', $card->filter('.crudit-header__user-card-name')->text());
        self::assertSame(['Administrator', 'alice@example.org'], $card->filter('.crudit-header__user-card-detail')->each(static fn ($p) => $p->text()));

        // Profile on Connect: an ordinary link, in a new tab, said to screen readers
        $profile = $menu->filter('a.crudit-ui-menu__item');
        self::assertSame('https://connect.example.org/profile', $profile->attr('href'));
        self::assertSame('_blank', $profile->attr('target'));
        self::assertSame('noopener', $profile->attr('rel'));
        self::assertNull($profile->attr('onclick'));
        self::assertSame('My profile (new tab)', preg_replace('/\s+/u', ' ', trim($profile->text())));
    }

    public function testUserWithoutCruditUserInterfaceShowsItsNameOnly(): void
    {
        $this->login('bob');
        $crawler = $this->client->request('GET', '/');

        self::assertSame('Bob Durand', $crawler->filter('.crudit-header__user-name')->text());
        self::assertCount(0, $crawler->filter('.crudit-header__user-role, .crudit-header__user-card-detail'));
    }

    public function testLogoutIsAPostWithItsCsrfToken(): void
    {
        $this->login('alice');
        $crawler = $this->client->request('GET', '/');

        $form = $crawler->filter('#crudit-user-menu form.crudit-ui-dropdown__footer');
        self::assertSame('post', $form->attr('method'));
        self::assertSame('/logout', $form->attr('action'));
        self::assertNotEmpty($form->filter('input[type="hidden"][name="_csrf_token"]')->attr('value'));
        self::assertSame('Logout', trim($form->filter('button[type="submit"].crudit-ui-menu__item--danger')->text()));

        $this->client->submit($form->form());
        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertCount(0, $this->client->getCrawler()->filter('#crudit-user-menu'));
    }

    public function testLogoutWithoutTokenIsRefused(): void
    {
        $this->login('alice');
        $this->client->request('POST', '/logout');
        $this->client->request('GET', '/');

        self::assertCount(1, $this->client->getCrawler()->filter('#crudit-user-menu'));
    }

    /**
     * @return iterable<string, array{?string, string}>
     */
    public static function themes(): iterable
    {
        yield 'system by default' => [null, 'system'];
        yield 'dark' => ['dark', 'dark'];
        yield 'garbage' => ['blue', 'system'];
    }

    #[DataProvider('themes')]
    public function testAppearanceChosenByTheServer(?string $cookie, string $pressed): void
    {
        if ($cookie !== null) {
            $this->client->getCookieJar()->set(new Cookie('crudit_ui_theme', $cookie));
        }
        $this->login('alice');
        $crawler = $this->client->request('GET', '/');

        $group = $crawler->filter('#crudit-user-menu [role="group"][aria-labelledby="crudit-user-menu-theme"]');
        self::assertSame('Appearance', $crawler->filter('#crudit-user-menu-theme')->text());
        self::assertSame('2lenet--crudit-ui-bundle--theme', $group->attr('data-controller'));
        self::assertSame(['light', 'dark', 'system'], $group->filter('button')->each(static fn ($b) => $b->attr('value')));
        self::assertSame([$pressed], $group->filter('button[aria-pressed="true"]')->each(static fn ($b) => $b->attr('value')));
    }

    public function testImpersonation(): void
    {
        $this->login('alice');
        $this->client->request('GET', '/?_switch_user=bob');
        $crawler = $this->client->followRedirect();

        // Banner above the bar: whose account, the way back (GET, with the name of the user who impersonates)
        $banner = $crawler->filter('.crudit-shell__header > .crudit-impersonation[role="region"][aria-label="Impersonation in progress"]');
        self::assertCount(1, $banner);
        self::assertSame('Bob Durand', $banner->filter('strong')->text());
        $back = $banner->filter('a.crudit-ui-btn--warning');
        self::assertSame('/?_switch_user=_exit', $back->attr('href'));
        self::assertSame('Back to my account (Alice Martin)', trim(preg_replace('/\s+/', ' ', $back->text()) ?? ''));

        // The menu: the avatar flagged, the way back too
        self::assertCount(1, $crawler->filter('button.crudit-header__user .crudit-ui-avatar--impersonated'));
        self::assertSame(['/?_switch_user=_exit'], $crawler->filter('#crudit-user-menu a[href*="_switch_user"]')->each(static fn ($a) => $a->attr('href')));

        $this->client->click($back->link());
        $crawler = $this->client->followRedirect();
        self::assertCount(0, $crawler->filter('.crudit-impersonation'));
        self::assertSame('Alice Martin', $crawler->filter('.crudit-header__user-name')->text());
    }

    private function login(string $identifier): void
    {
        $this->client->loginUser((new UserProvider())->loadUserByIdentifier($identifier));
    }
}
