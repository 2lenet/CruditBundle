<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Lle\CruditBundle\Entity\SavedFilter;
use Lle\CruditBundle\Filter\SavedFilterManager;

/**
 * The sets of filters a user saves for a list (lot 3.1.f): three POST routes with a CSRF token, per user, on SQLite.
 * Alice and Bob sign in with http_basic (their password is their name).
 */
class SavedFiltersTest extends AppTestCase
{
    /** @var array<string, string> */
    private array $server = [];

    private function signIn(string $user): void
    {
        $this->client->disableReboot();
        $this->server = ['PHP_AUTH_USER' => $user, 'PHP_AUTH_PW' => $user];
    }

    /**
     * A token of the session of the browser: the page is requested, then the token is made in that session.
     */
    private function token(): string
    {
        $this->client->request('GET', '/client/', server: $this->server);
        $container = static::getContainer();
        $requestStack = $container->get('request_stack');
        $request = $this->client->getRequest();
        $requestStack->push($request);
        $token = $container->get(SavedFilterManager::class)->createToken();
        $request->getSession()->save();
        $requestStack->pop();

        return $token;
    }

    /**
     * @param array<string, string> $fields
     */
    private function post(string $path, array $fields = [], ?string $token = null): void
    {
        $this->client->request('POST', $path, ['_token' => $token ?? $this->token(), 'redirect' => '/client/'] + $fields, server: $this->server);
    }

    /**
     * @return list<SavedFilter>
     */
    private function sets(): array
    {
        return static::getContainer()->get(EntityManagerInterface::class)->getRepository(SavedFilter::class)->findBy([], ['id' => 'ASC']);
    }

    /**
     * @return list<string>
     */
    private function flashes(): array
    {
        return array_merge(...array_values($this->client->getRequest()->getSession()->getFlashBag()->peekAll()));
    }

    public function testTheTableOfTheEntityExists(): void
    {
        self::assertSame([], $this->sets());
    }

    public function testAnAnonymousUserHasNoSavedFilters(): void
    {
        $this->client->request('POST', '/crudit/filters/client/saved', ['name' => 'Mine', 'filter_client_name_op' => 'contains', 'filter_client_name_value' => 'ali']);

        self::assertResponseStatusCodeSame(404);
        self::assertSame([], $this->sets());
    }

    public function testAListThatDoesNotExistHasNone(): void
    {
        $this->signIn('alice');
        $this->post('/crudit/filters/nothing/saved', ['name' => 'Mine']);

        self::assertResponseStatusCodeSame(404);
    }

    public function testATokenIsRequired(): void
    {
        $this->signIn('alice');
        $this->token();
        $this->client->request('POST', '/crudit/filters/client/saved', ['name' => 'Mine', 'filter_client_name_op' => 'contains', 'filter_client_name_value' => 'ali', '_token' => 'forged'], server: $this->server);

        self::assertResponseStatusCodeSame(403);
        self::assertSame([], $this->sets());
    }

    public function testAGetNeverChangesAnything(): void
    {
        $this->signIn('alice');
        $this->client->request('GET', '/crudit/filters/client/saved', server: $this->server);

        self::assertResponseStatusCodeSame(405);
    }

    public function testTheFiltersOfThePanelAreSavedAndApplied(): void
    {
        $this->signIn('alice');
        $this->post('/crudit/filters/client/saved', [
            'name' => 'Alice only',
            'filter_client_name_op' => 'contains', 'filter_client_name_value' => 'ali',
            'filter_client_notes_op' => 'contains', 'filter_client_notes_value' => 'hidden',
        ]);

        self::assertResponseRedirects('/client/', 303);
        [$saved] = $this->sets();
        self::assertSame('alice', $saved->getOwner());
        self::assertSame('client', $saved->getFilterset());
        self::assertSame(['name' => ['op' => 'contains', 'value' => 'ali']], $saved->getCriteria(), 'a hidden filter is not saved');
        self::assertSame(['The filters “Alice only” are saved.'], $this->flashes());

        // They are the filters in use now.
        $crawler = $this->client->request('GET', '/client/', server: $this->server);
        self::assertCount(1, $crawler->filter('main tbody tr.crudit-ui-table__row'));
        self::assertStringContainsString('Alice', $crawler->filter('main tbody')->text());
    }

    public function testASavedSetIsRecalledAndDeleted(): void
    {
        $this->signIn('alice');
        $this->post('/crudit/filters/client/saved', ['name' => 'Alice only', 'filter_client_name_op' => 'contains', 'filter_client_name_value' => 'ali']);
        $id = $this->sets()[0]->getId();
        // The page is now filtered by something else.
        $this->client->request('GET', '/client/?filter_client_name_op=contains&filter_client_name_value=bob&client_filter=filter', server: $this->server);
        self::assertCount(1, $this->client->getCrawler()->filter('main tbody tr.crudit-ui-table__row'));

        $this->post('/crudit/filters/client/saved/' . $id . '/apply');
        self::assertResponseRedirects('/client/', 303);
        $crawler = $this->client->request('GET', '/client/', server: $this->server);
        self::assertStringContainsString('Alice', $crawler->filter('main tbody')->text());
        self::assertStringNotContainsString('Bob', $crawler->filter('main tbody')->text());

        $this->post('/crudit/filters/client/saved/' . $id . '/delete');
        self::assertResponseRedirects('/client/', 303);
        self::assertSame([], $this->sets());
    }

    public function testTheListShowsMyFiltersWithTheSetsOfTheUser(): void
    {
        $this->signIn('alice');
        $this->post('/crudit/filters/client/saved', ['name' => 'Alice only', 'filter_client_name_op' => 'contains', 'filter_client_name_value' => 'ali', 'filter_client_active_op' => 'eq', 'filter_client_active_value' => 'true']);
        $this->post('/crudit/filters/client/saved', ['name' => 'Bob', 'filter_client_name_op' => 'contains', 'filter_client_name_value' => 'bob']);
        $bob = $this->sets()[1];

        // Bob is the set in use: his filters were applied after being saved.
        $crawler = $this->client->request('GET', '/client/', server: $this->server);
        $saved = $crawler->filter('main .crudit-ui-filter-saved');
        self::assertCount(1, $saved);
        self::assertSame(['Alice only', 'Bob'], $saved->filter('.crudit-ui-filter-saved__name')->each(static fn ($node): string => $node->text()));
        $items = $saved->filter('.crudit-ui-filter-saved__item');
        self::assertCount(1, $items->eq(1)->filter('a[aria-current=true]'), 'the set in use is a link that removes its filters');
        self::assertStringContainsString('client_reset=1', $items->eq(1)->filter('a')->attr('href'));
        self::assertStringContainsString('Name: bob', $items->eq(1)->text());
        self::assertStringContainsString('Name: ali', $items->eq(0)->text());
        self::assertSame('/crudit/filters/client/saved/' . $this->sets()[0]->getId() . '/apply', $items->eq(0)->filter('button.crudit-ui-filter-saved__apply')->attr('formaction'));
        self::assertSame('/crudit/filters/client/saved/' . $bob->getId() . '/delete', $items->eq(1)->filter('.crudit-ui-filter-saved__confirm button[formaction]')->attr('formaction'));

        // The token and the way back are in the form outside the filters; the GET form carries them off.
        $outside = $crawler->filter('main form[method=post]#crudit-filters-client-saved-form');
        self::assertCount(1, $outside);
        self::assertMatchesRegularExpression('/^[\w.-]{20,}$/', $outside->filter('input[name=_token]')->attr('value'));
        self::assertSame('/client/', $outside->filter('input[name=redirect]')->attr('value'));
        self::assertCount(2, $crawler->filter('main form.crudit-ui-filter-bar input[disabled]')->reduce(static fn ($node): bool => in_array($node->attr('name'), ['_token', 'redirect'], true)));
        self::assertNull($crawler->filter('main form.crudit-ui-filter-bar #crudit-filters-client-save-name')->attr('name'));
        self::assertStringContainsString('saved-filters', $crawler->filter('main form.crudit-ui-filter-bar')->attr('data-controller'));
    }

    public function testNoMyFiltersWithoutAUser(): void
    {
        $crawler = $this->client->request('GET', '/client/');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('.crudit-ui-filter-saved'));
        self::assertCount(0, $crawler->filter('.crudit-ui-filter-save'));
        self::assertStringNotContainsString('saved-filters', $crawler->filter('main form.crudit-ui-filter-bar')->attr('data-controller'));
    }

    public function testAnotherUserDoesNotSeeTheSets(): void
    {
        $this->signIn('alice');
        $this->post('/crudit/filters/client/saved', ['name' => 'Alice only', 'filter_client_name_op' => 'contains', 'filter_client_name_value' => 'ali']);

        $this->signIn('bob');
        $crawler = $this->client->request('GET', '/client/', server: $this->server);
        self::assertCount(0, $crawler->filter('.crudit-ui-filter-saved__item'));
        self::assertCount(1, $crawler->filter('.crudit-ui-filter-saved__empty'));
    }

    public function testASetOfAnotherUserIsNotFound(): void
    {
        $this->signIn('alice');
        $this->post('/crudit/filters/client/saved', ['name' => 'Alice only', 'filter_client_name_op' => 'contains', 'filter_client_name_value' => 'ali']);
        $id = $this->sets()[0]->getId();

        $this->signIn('bob');
        $this->post('/crudit/filters/client/saved/' . $id . '/delete');
        self::assertResponseRedirects('/client/', 303);
        self::assertCount(1, $this->sets(), 'Bob cannot delete the set of Alice');
        self::assertSame(['These filters no longer exist.'], $this->flashes());
    }

    public function testNothingToSaveWithoutACriterion(): void
    {
        $this->signIn('alice');
        $this->post('/crudit/filters/client/saved', ['name' => 'Nothing']);

        self::assertResponseRedirects('/client/', 303);
        self::assertSame([], $this->sets());
    }

    /**
     * @dataProvider redirections
     */
    public function testTheReturnAddressIsAnAddressOfTheSite(string $redirect, string $expected): void
    {
        $this->signIn('alice');
        $this->client->request('POST', '/crudit/filters/client/saved', ['_token' => $this->token(), 'name' => 'Mine', 'redirect' => $redirect, 'filter_client_name_op' => 'contains', 'filter_client_name_value' => 'ali'], server: $this->server);

        self::assertResponseRedirects($expected, 303);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function redirections(): iterable
    {
        yield 'a path with a query' => ['/client/?page=2', '/client/?page=2'];
        yield 'another site' => ['https://evil.example/', '/'];
        yield 'a protocol-relative address' => ['//evil.example/', '/'];
        yield 'a backslash' => ['/\\evil.example', '/'];
        yield 'none' => ['', '/'];
    }
}
