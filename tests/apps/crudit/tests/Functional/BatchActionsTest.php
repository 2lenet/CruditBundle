<?php

declare(strict_types=1);

namespace App\Tests\Functional;

/**
 * Lot 3.1.c: the selection of the rows and the bar of the batch actions (SelectionBar of CruditUiBundle).
 */
class BatchActionsTest extends AppTestCase
{
    private const string BAR = '2lenet--crudit-ui-bundle--selection-bar';

    public function testTheRowsHaveABoxAndTheBarIsThere(): void
    {
        $crawler = $this->client->request('GET', '/client/');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('main thead input[type="checkbox"]'));
        self::assertCount(3, $crawler->filter('main tbody tr.crudit-ui-table__row input[type="checkbox"]'));
        $bar = $crawler->filter('main [data-controller="' . self::BAR . '"]');
        self::assertCount(1, $bar);
        self::assertNotSame('', (string) $bar->attr('data-' . self::BAR . '-table-value'));
        self::assertSame('3', $bar->attr('data-' . self::BAR . '-total-value'));
        self::assertNotNull($crawler->filter('main [role="region"][hidden]')->getNode(0), 'hidden until a row is checked');
    }

    public function testTheActionsAreTheBatchActionsOnly(): void
    {
        $crawler = $this->client->request('GET', '/client/');

        $actions = $crawler->filter('main .crudit-ui-selection-bar__actions');
        self::assertStringContainsString('Export', $actions->text());
        self::assertStringContainsString('Change the status', $actions->text());
        self::assertStringContainsString('Archive', $actions->text());
        self::assertStringNotContainsString('About', $actions->text(), 'an action of the list is not a batch action');
    }

    public function testALinkReceivesTheSelection(): void
    {
        $crawler = $this->client->request('GET', '/client/');

        $link = $crawler->filter('main .crudit-ui-selection-bar__actions a[href*="/client/batch/export"]');
        self::assertCount(1, $link);
        self::assertSame('click->' . self::BAR . '#fill', $link->attr('data-action'));
        self::assertNull($link->getNode(0)?->attributes?->getNamedItem('target'), 'no target="" when there is none');
    }

    public function testAFreeModalIsFedWithTheSelectionWhenItOpens(): void
    {
        $crawler = $this->client->request('GET', '/client/');

        $buttons = $crawler->filter('main .crudit-ui-selection-bar__actions button[command="show-modal"][data-action$="selection-bar#fillDialog"]');
        $button = $buttons->reduce(static fn ($node) => str_contains($node->text(), 'Send a message'));
        self::assertCount(1, $button);
        $dialog = $crawler->filter('main dialog[id="' . $button->attr('commandfor') . '"]');
        self::assertCount(1, $dialog);
        self::assertCount(1, $dialog->filter('input[data-selection-field="ids"]'));
        self::assertCount(1, $dialog->filter('input[data-selection-field="all_page"]'));
    }

    public function testAnIconOnlyActionKeepsItsLabelForScreenReaders(): void
    {
        $crawler = $this->client->request('GET', '/client/');

        $link = $crawler->filter('main .crudit-ui-selection-bar__actions a[href*="/client/batch/export"]');
        self::assertCount(1, $link);
        self::assertCount(1, $link->filter('.crudit-ui-visually-hidden'));
        self::assertSame('Export', trim($link->filter('.crudit-ui-visually-hidden')->text()));
    }

    public function testTheTemplateOfAnActionReplacesItsButton(): void
    {
        $crawler = $this->client->request('GET', '/client/');

        $custom = $crawler->filter('main .crudit-ui-selection-bar__actions a#batch-custom');
        self::assertCount(1, $custom);
        self::assertSame('Custom button', trim($custom->text()));
        self::assertNotSame('', (string) $custom->attr('data-view'), 'the template sees the view');
    }

    public function testAFreeModalKeepsItsIdOf1xAndSeesTheView(): void
    {
        $crawler = $this->client->request('GET', '/client/');

        $dialog = $crawler->filter('main dialog[id^="modal_"] [data-view]');
        self::assertCount(1, $dialog, 'the id is modal_<id of the action>, as before 2.0');
        self::assertNotSame('', (string) $dialog->attr('data-view'), 'the modal sees the view');
    }

    public function testTheIdOfTheTableIsNotThatOfTheWrapperOfTheBrick(): void
    {
        $crawler = $this->client->request('GET', '/client/');

        $ids = $crawler->filter('main [id]')->each(static fn ($node) => $node->attr('id'));
        self::assertSame($ids, array_values(array_unique($ids)), 'an id is unique in the page');
    }

    public function testTheFormIsInADialogWithTheFieldsOfTheSelection(): void
    {
        $crawler = $this->client->request('GET', '/client/');

        $form = $crawler->filter('main dialog form[action$="/client/batch/status"]');
        self::assertCount(1, $form);
        self::assertSame('submit->' . self::BAR . '#fill', $form->attr('data-action'));
        self::assertCount(1, $form->filter('input[type="hidden"][data-selection-field="ids"]'));
        self::assertCount(1, $form->filter('input[type="hidden"][data-selection-field="all_page"]'));
        self::assertCount(1, $form->filter('select'));
        self::assertCount(1, $form->filter('input[name="client_batch[_token]"]'), 'the CSRF token of the form is kept');
    }

    public function testTheConfirmationNamesTheAction(): void
    {
        $crawler = $this->client->request('GET', '/client/');

        $dialog = $crawler->filter('main dialog:has(a[href*="/client/batch/archive"])');
        self::assertCount(1, $dialog);
        self::assertStringContainsString('Archive the selected clients?', $dialog->text());
    }

    public function testTheFormIsSentWithTheSelection(): void
    {
        $crawler = $this->client->request('GET', '/client/');
        $form = $crawler->filter('main dialog form[action$="/client/batch/status"]')->form();
        $values = $form->getPhpValues();
        $values['client_batch']['ids'] = '1,2';
        $values['client_batch']['all_page'] = '1';
        $values['client_batch']['status'] = 'lost';

        $this->client->request('POST', $form->getUri(), $values);
        $this->client->followRedirect();

        self::assertSelectorTextContains('body', 'status: ids=1,2 all_page=1');
    }
}
