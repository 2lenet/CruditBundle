<?php

declare(strict_types=1);

namespace App\Tests\Functional;

/**
 * The pager of the list (lot 3.1.d): the Pager of CruditUiBundle fed by `crudit_pager()`, with the parameters of the
 * 1.x (`client_offset`, `client_limit`). The database has 3 clients (Alice, Bob, Chloé).
 */
class PagerTest extends AppTestCase
{
    /**
     * @return list<string>
     */
    private function names(string $uri): array
    {
        $crawler = $this->client->request('GET', $uri);
        self::assertResponseIsSuccessful();

        return $crawler->filter('main tbody tr.crudit-ui-table__row th[scope=row]')->each(fn ($th) => trim($th->text()));
    }

    public function testOnePageHasTheSummaryOnly(): void
    {
        $crawler = $this->client->request('GET', '/client/');

        self::assertCount(1, $crawler->filter('main .crudit-ui-pager'));
        self::assertMatchesRegularExpression('/^1 to 3 of 3 /', trim($crawler->filter('main .crudit-ui-pager__summary')->text()));
        self::assertCount(0, $crawler->filter('main .crudit-ui-pager nav'), 'a single page has no navigation');
        self::assertCount(0, $crawler->filter('main .crudit-ui-pager form'), '3 rows fit the smallest choice: no selector');
    }

    public function testThePagesAreLinksWithTheParametersOfTheFirstVersion(): void
    {
        $crawler = $this->client->request('GET', '/client/?client_limit=2&client_sort=name&client_sort_order=desc');

        self::assertSame(['Chloé', 'Bob'], $crawler->filter('main tbody tr.crudit-ui-table__row th[scope=row]')->each(fn ($th) => trim($th->text())));
        self::assertMatchesRegularExpression('/^1 to 2 of 3 /', trim($crawler->filter('main .crudit-ui-pager__summary')->text()));
        self::assertSame('page', $crawler->filter('main .crudit-ui-pager [aria-current]')->attr('aria-current'));
        $next = $crawler->filter('main .crudit-ui-pager__number a')->attr('href');
        self::assertStringContainsString('client_offset=2', $next);
        self::assertStringContainsString('client_sort=name', $next, 'the sort stays');
        self::assertStringContainsString('client_limit=2', $next, 'the number of rows stays: the pager only changes the page');
    }

    public function testTheSecondPage(): void
    {
        self::assertSame(['Alice'], $this->names('/client/?client_limit=2&client_offset=2&client_sort=name&client_sort_order=desc'), 'Chloé, Bob, then Alice');
        $crawler = $this->client->request('GET', '/client/?client_limit=2&client_offset=2');

        self::assertMatchesRegularExpression('/^3 to 3 of 3 /', trim($crawler->filter('main .crudit-ui-pager__summary')->text()));
        self::assertCount(2, $crawler->filter('main .crudit-ui-pager [aria-disabled="true"]'), 'next and last: no dead link');
        self::assertStringContainsString('client_offset=0', $crawler->filter('main .crudit-ui-pager__edge a')->first()->attr('href'));
    }

    public function testThePageBeyondTheLastGoesToTheLastOne(): void
    {
        // 3 clients, 2 a page: the last row of the last page was deleted, or the address is old
        self::assertSame(['Chloé'], $this->names('/client/?client_limit=2&client_offset=50'));
        $crawler = $this->client->request('GET', '/client/?client_limit=2&client_offset=50');
        self::assertMatchesRegularExpression('/^3 to 3 of 3 /', trim($crawler->filter('main .crudit-ui-pager__summary')->text()));

        // the session holds the last page too: the next visit, without parameters, does not start at 50
        self::assertSame(['Chloé'], $this->names('/client/'));
    }

    public function testAnOffsetThatIsNotTheStartOfAPageGoesBackToTheStartOfItsPage(): void
    {
        // 30 rows at the limit of 50 (an old address, a limit that changed) would show the rows 31 to 80 as the page 1
        $crawler = $this->client->request('GET', '/client/?client_limit=2&client_offset=1');

        self::assertSame(['Alice', 'Bob'], $crawler->filter('main tbody tr.crudit-ui-table__row th[scope=row]')->each(fn ($th) => trim($th->text())));
        self::assertMatchesRegularExpression('/^1 to 2 of 3 /', trim($crawler->filter('main .crudit-ui-pager__summary')->text()));
    }

    public function testAnEmptyListHasNoPagerButTheEmptyState(): void
    {
        $crawler = $this->client->request('GET', '/client/?filter_client_name_op=contains&filter_client_name_value=nobody-has-this-name&client_filter=filter');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('main .crudit-ui-pager'), 'no result: no pager, the table says it');
        self::assertCount(0, $crawler->filter('main tbody tr.crudit-ui-table__row'));
        self::assertCount(1, $crawler->filter('main .crudit-ui-empty-state'));
    }

    public function testTheRowsPerPageAreAFormThatGoesBackToTheFirstPage(): void
    {
        $crawler = $this->client->request('GET', '/client/?client_limit=2&client_offset=2&client_sort=name');
        // 3 rows and the choices of AbstractCrudConfig [10, 30, 50, 100]: the limit 2 joins them
        self::assertCount(1, $crawler->filter('main .crudit-ui-pager form'));
        $form = $crawler->filter('main .crudit-ui-pager form');
        self::assertSame('get', $form->attr('method'));
        self::assertSame(['2', '10', '30', '50', '100'], $form->filter('select option')->each(fn ($o) => $o->attr('value')));
        self::assertSame('client_limit', $form->filter('select')->attr('name'));
        $fields = $form->filter('input[type=hidden]')->each(fn ($i) => $i->attr('name'));
        self::assertContains('client_sort', $fields, 'the other parameters stay');
        self::assertNotContains('client_limit', $fields, 'the select sends the rows per page');

        // the form sends the first page: the offset 2 of the session is not read again
        self::assertSame('0', $form->filter('input[name=client_offset]')->attr('value'));
        $crawler = $this->client->submit($form->form(['client_limit' => '10']));
        self::assertSame(['Alice', 'Bob', 'Chloé'], $crawler->filter('main tbody tr.crudit-ui-table__row th[scope=row]')->each(fn ($th) => trim($th->text())));
    }

    public function testTheOldPagerIsGone(): void
    {
        $crawler = $this->client->request('GET', '/client/?client_limit=2');

        self::assertCount(0, $crawler->filter('main ul.pagination'));
        self::assertCount(0, $crawler->filter('main #items-per-page'));
        self::assertCount(1, $crawler->filter('main .crudit-ui-pager'), 'one pager, at the bottom, not two');
    }
}
