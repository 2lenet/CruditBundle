<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Component\DomCrawler\Crawler;

/**
 * The filters of a list (lot 3.1.e): the Filter:Bar of CruditUiBundle fed by the FilterBrick, a GET form whose state is
 * kept in the session (the clients: ClientFilterset, one filter of each type).
 */
class FiltersTest extends AppTestCase
{
    /** The names of the rows of the list, in the page. */
    private function names(Crawler $crawler): array
    {
        return $crawler->filter('main tbody tr.crudit-ui-table__row')->each(static function (Crawler $row): string {
            foreach (['Alice', 'Bob', 'Chloé'] as $name) {
                if (str_contains($row->text(), $name)) {
                    return $name;
                }
            }

            return $row->text();
        });
    }

    private function rows(string $query = ''): Crawler
    {
        return $this->client->request('GET', '/client/' . $query);
    }

    public function testTheBarIsThePanelAndTheMainFilters(): void
    {
        $crawler = $this->rows();

        self::assertResponseIsSuccessful();
        $form = $crawler->filter('main form.crudit-ui-filter-bar');
        self::assertCount(1, $form);
        self::assertSame('search', $form->attr('role'));
        self::assertSame('get', $form->attr('method'));
        self::assertCount(2, $form->filter('.crudit-ui-filter-bar__quick'), 'the name and the status are the main filters');
        self::assertCount(1, $form->filter('.crudit-ui-filter-bar__quick [name="filter_client_name_value"]'));
        self::assertCount(3, $form->filter('.crudit-ui-filter-bar__quick [name="filter_client_status_value[]"]'));
        $panel = $form->filter('dialog.crudit-ui-filter-panel');
        foreach (['active_value', 'amount_value', 'progress_value', 'progress_to', 'createdAt_value', 'createdAt_to', 'referrer_value[]', 'email_value'] as $field) {
            self::assertGreaterThan(0, count($panel->filter(sprintf('[name="filter_client_%s"]', $field))), $field);
        }
        self::assertCount(0, $form->filter('[name^="filter_client_name"]')->reduce(static fn (Crawler $node): bool => $node->closest('dialog') !== null), 'a main filter is not twice');
        self::assertCount(0, $form->filter('[name^="filter_client_notes"]'), 'a hidden filter shows nowhere');
        self::assertSame('filter', $form->filter('input[type=hidden][name=client_filter]')->attr('value'));
    }

    public function testTheEntityFilterListsTheEntities(): void
    {
        $crawler = $this->rows();

        $labels = $crawler->filter('[name="filter_client_referrer_value[]"]')->each(static fn (Crawler $box): string => trim($box->closest('label')->text()));
        self::assertSame(['Alice', 'Bob', 'Chloé'], $labels);
    }

    public function testAFilterWithATemplateOfTheProjectKeepsIt(): void
    {
        $crawler = $this->rows();

        $field = $crawler->filter('dialog.crudit-ui-filter-panel [data-filter-id="email"]');
        self::assertCount(1, $field);
        self::assertCount(1, $field->filter('input.email-filter[type=email][name="filter_client_email_value"]'), 'its own block input');
        self::assertCount(1, $field->filter('.crudit-ui-filter-field__control'), 'in the frame of the bundle');
        self::assertGreaterThan(1, count($field->filter('[name="filter_client_email_op"]')), 'the conditions of the type');
    }

    public function testAFilterIsAppliedAndKeptWhileTheUserComesBack(): void
    {
        $crawler = $this->rows('?filter_client_name_op=contains&filter_client_name_value=ali&client_filter=filter');
        self::assertSame(['Alice'], $this->names($crawler));

        // The list found again, with no parameter: still filtered (the session), and the filter is shown.
        $crawler = $this->rows();
        self::assertSame(['Alice'], $this->names($crawler));
        self::assertSame('ali', $crawler->filter('[name="filter_client_name_value"]')->attr('value'));
        self::assertStringContainsString('ali', $crawler->filter('.crudit-ui-filter-bar__quick-button--active')->text());
    }

    public function testTheBoxesOfAListAreSentAsAnArray(): void
    {
        $crawler = $this->rows('?filter_client_status_op=in&filter_client_status_value[]=prospect&filter_client_status_value[]=customer');

        self::assertSame(['Alice', 'Bob'], $this->names($crawler));
        self::assertCount(2, $crawler->filter('[name="filter_client_status_value[]"][checked]'));
    }

    public function testAFilterThatSendsItsConditionWithNothingElseIsEmptied(): void
    {
        $this->rows('?filter_client_status_op=in&filter_client_status_value[]=prospect');
        $crawler = $this->rows('?filter_client_status_op=in');

        self::assertCount(3, $this->names($crawler), 'no box checked: the filter is empty, not kept');
        self::assertCount(0, $crawler->filter('[name="filter_client_status_value[]"][checked]'));
    }

    public function testAFilterThatIsNotInTheFormIsLeftAlone(): void
    {
        $this->rows('?filter_client_status_op=in&filter_client_status_value[]=prospect');
        $crawler = $this->rows('?filter_client_name_op=contains&filter_client_name_value=o');

        self::assertSame(['Bob'], $this->names($crawler), 'the name filter is applied, with the status that stays');
        self::assertCount(1, $crawler->filter('[name="filter_client_status_value[]"][checked]'), 'the status stays');
    }

    public function testAnUnknownConditionIsRefusedWithItsValue(): void
    {
        $this->rows('?filter_client_name_op=contains&filter_client_name_value=ali');
        $crawler = $this->rows('?filter_client_name_op=1%27%20OR%201%3D1&filter_client_name_value=bob');

        self::assertResponseIsSuccessful();
        self::assertSame(['Alice'], $this->names($crawler), 'the filter keeps what it had');
        self::assertSame('ali', $crawler->filter('[name="filter_client_name_value"]')->attr('value'));
    }

    public function testAValueOfTheWrongShapeIsIgnored(): void
    {
        $this->rows('?filter_client_name_op=contains&filter_client_name_value=ali');
        $crawler = $this->rows('?filter_client_name_value[a][b]=x&filter_client_amount_value[]=1&filter_client_amount_value[][]=2');

        self::assertResponseIsSuccessful();
        self::assertSame(['Alice'], $this->names($crawler));
    }

    public function testAVeryLongValueIsCut(): void
    {
        $this->rows('?filter_client_name_op=contains&filter_client_name_value=' . str_repeat('a', 5000));

        $crawler = $this->rows();
        self::assertSame(1000, mb_strlen((string) $crawler->filter('[name="filter_client_name_value"]')->attr('value')));
    }

    public function testAHiddenFilterStillFiltersButShowsNowhere(): void
    {
        $crawler = $this->rows('?filter_client_notes_op=contains&filter_client_notes_value=good');

        self::assertSame(['Alice'], $this->names($crawler));
        self::assertCount(0, $crawler->filter('[name^="filter_client_notes"]'));
        self::assertCount(0, $crawler->filter('ul.crudit-ui-chips li'), 'no chip');
    }

    public function testChipsAreTheActiveFiltersOutsideTheBar(): void
    {
        $crawler = $this->rows('?filter_client_active_op=eq&filter_client_active_value=true&filter_client_name_op=contains&filter_client_name_value=a');

        $chips = $crawler->filter('main ul.crudit-ui-chips > li');
        self::assertCount(1, $chips, 'the name is a main filter: its value is in its button');
        self::assertStringContainsString('Yes', $chips->text());
        self::assertSame('active', $chips->filter('button')->attr('data-2lenet--crudit-ui-bundle--filters-id-param'));
    }

    public function testResetBringsTheDefaultsBack(): void
    {
        $this->rows('?filter_client_name_op=contains&filter_client_name_value=ali');
        $crawler = $this->rows('?client_reset=1');

        self::assertCount(3, $this->names($crawler));
        self::assertSame('', (string) $crawler->filter('[name="filter_client_name_value"]')->attr('value'));
        $reset = $crawler->filter('dialog.crudit-ui-filter-panel a.crudit-ui-btn--ghost');
        self::assertSame('/client/?client_reset=1', $reset->attr('href'));
    }
}
