<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Lot 3.1.a: the actions of the header of the CRUD pages (brick/links/_actions): Buttons of CruditUiBundle, one
 * primary action, the menu "More" (the secondary ones below md), a DropdownAction, a print, a confirmation that names
 * the element and modals opened by commandfor, the reasons of the disabled actions, no Bootstrap left. The actions of
 * the lines of the list (brick/list_items/_actions): the edition, then a menu named after the line.
 */
class PageActionsTest extends AppTestCase
{
    public function testActionsOfTheList(): void
    {
        $crawler = $this->client->request('GET', '/client/');
        $bar = $crawler->filter('.crudit-ui-page-header .crudit-page-actions');

        self::assertSame(['Back to the list', 'Add', 'Export', 'Reports', 'Print'], $bar->filter('.crudit-page-actions__item')->each(static fn (Crawler $item): string => trim($item->filter('.crudit-ui-btn')->first()->text())));
        self::assertCount(1, $bar->filter('.crudit-ui-btn--primary'));
        self::assertSame('/client/new', $bar->filter('.crudit-page-actions__primary a')->attr('href'));
        self::assertSame('noreferrer', $bar->filter('a[rel]')->attr('rel'));
        self::assertSame('2lenet--crudit-bundle--print#print', $bar->filter('.crudit-page-actions__item button[data-action*="print"]')->attr('data-action'));

        // The export opens its dialog, rendered after the bar
        $export = $bar->filter('.crudit-page-actions__secondary button[command="show-modal"]')->first();
        self::assertStringStartsWith('modal_', (string) $export->attr('commandfor'));
        self::assertCount(1, $crawler->filter('dialog#' . $export->attr('commandfor')));

        // The DropdownAction: its groups, a heading for a named one
        $reports = $bar->filter('.crudit-page-actions__secondary .crudit-ui-dropdown')->first();
        self::assertSame('Sales', $reports->filter('.crudit-ui-menu__heading')->text());
        self::assertSame(['Report of the month', 'Custom report'], $reports->filter('.crudit-ui-menu__text')->each(static fn (Crawler $item): string => $item->text()));

        // The menu "More", named: the secondary ones for the phones first, then its own actions by group
        $more = $bar->filter('.crudit-page-actions__more');
        self::assertStringNotContainsString('--mobile-only', (string) $more->attr('class'));
        self::assertSame('More actions', $more->filter('button .crudit-ui-visually-hidden')->first()->text());
        $own = $more->filter('.crudit-ui-menu > li')->reduce(static fn (Crawler $li): bool => $li->filter('.crudit-page-actions__mobile-only')->count() === 0 && !str_contains((string) $li->attr('class'), 'mobile-only'));
        self::assertSame(['About'], $own->filter(':scope > .crudit-ui-menu__item .crudit-ui-menu__text')->each(static fn (Crawler $item): string => $item->text()));
        self::assertSame('Maintenance', $own->filter('.crudit-ui-menu__heading')->text());
        self::assertSame(['Archive the inactive clients', 'Purge the historyYou do not have the rights for this action'], $own->filter('.crudit-ui-menu__sub .crudit-ui-menu__text')->each(static fn (Crawler $item): string => $item->text()));
        $archive = $more->filter('.crudit-ui-menu__item--danger');
        self::assertSame('Archive all the inactive clients?', $archive->attr('data-confirm-text'));
        self::assertSame('true', $more->filter('.crudit-ui-menu__group button[aria-disabled]')->attr('aria-disabled'));

        $this->assertNoBootstrap($crawler);
    }

    public function testActionsOfTheShow(): void
    {
        $crawler = $this->client->request('GET', '/client/show/' . $this->idOf('Chloé'));
        $bar = $crawler->filter('.crudit-ui-page-header .crudit-page-actions');

        self::assertSame('Edit', trim($bar->filter('.crudit-page-actions__primary')->text()));

        // The confirmation names the element, its button is the action, in danger
        $delete = $bar->filter('.crudit-page-actions__secondary button.crudit-ui-btn--danger');
        self::assertSame('crudit-confirm', $delete->attr('commandfor'));
        self::assertSame('show-modal', $delete->attr('command'));
        self::assertMatchesRegularExpression('#^/client/delete/\d+$#', (string) $delete->attr('data-confirm-link'));
        self::assertSame('Delete “Chloé”?', $delete->attr('data-confirm-text'));
        self::assertSame('Delete', $delete->attr('data-confirm-label'));
        self::assertSame('true', $delete->attr('data-confirm-danger'));
        self::assertNull($delete->attr('href'));

        // A modal with fields, opened by its button; a reason of the business on "Deactivate"
        $email = $bar->filter('button[commandfor^="modal_"]');
        self::assertCount(1, $crawler->filter('dialog#' . $email->attr('commandfor') . ' form[method="post"] textarea'));
        $deactivate = $bar->filter('.crudit-page-actions__item')->reduce(static fn (Crawler $item): bool => str_contains($item->text(), 'Deactivate'));
        self::assertSame('true', $deactivate->filter('button')->attr('aria-disabled'));
        self::assertSame('This client is already inactive', $crawler->filter('#' . $deactivate->filter('button')->attr('aria-describedby'))->text());

        $this->assertNoBootstrap($crawler);
    }

    public function testReasonOfAnActiveClient(): void
    {
        $crawler = $this->client->request('GET', '/client/show/' . $this->idOf('Bob'));
        $delete = $crawler->filter('.crudit-page-actions__secondary')->reduce(static fn (Crawler $item): bool => str_contains($item->text(), 'Delete'));
        $button = $delete->filter('button');

        // Disabled, no colour of danger, still focusable, described by its reason; no confirmation
        self::assertSame('true', $button->attr('aria-disabled'));
        self::assertStringContainsString('crudit-ui-btn--secondary', (string) $button->attr('class'));
        self::assertNull($button->attr('commandfor'));
        self::assertSame('An active client cannot be deleted: deactivate it first', $crawler->filter('#' . $button->attr('aria-describedby'))->text());
    }

    public function testActionsOfTheLines(): void
    {
        $crawler = $this->client->request('GET', '/client/');
        $row = $crawler->filter('tbody tr')->reduce(static fn (Crawler $tr): bool => str_contains($tr->text(), 'Chloé'))->first();
        $actions = $row->filter('.crudit-row-actions');

        // The edition, an icon button named after the line; an action setDropdown(false) shown, a button at its label
        // named after the line, disabled with its reason; the others in the menu of the line
        $edit = $actions->filter('a.crudit-ui-icon-btn');
        self::assertSame('Edit Chloé', $edit->text());
        self::assertMatchesRegularExpression('#^/client/edit/\d+$#', (string) $edit->attr('href'));
        $deactivate = $actions->filter('button.crudit-ui-btn');
        self::assertSame('Deactivate', $deactivate->text());
        self::assertSame('Deactivate Chloé', $deactivate->attr('aria-label'));
        // A button at its label: a tooltip for its reason only, not to repeat its label
        self::assertSame('This client is already inactive', $actions->filter('.crudit-ui-tooltip-host:has(> .crudit-ui-btn) .crudit-ui-tooltip')->text());
        $bob = $crawler->filter('tbody tr')->reduce(static fn (Crawler $tr): bool => str_contains($tr->text(), 'Bob'))->first()->filter('.crudit-row-actions');
        self::assertCount(1, $bob->filter('.crudit-ui-btn'));
        self::assertCount(0, $bob->filter('.crudit-ui-tooltip-host .crudit-ui-btn'));
        self::assertSame('true', $deactivate->attr('aria-disabled'));
        self::assertSame('This client is already inactive', $row->filter('#' . $deactivate->attr('aria-describedby'))->text());
        self::assertSame('More actions for Chloé', $actions->filter('.crudit-ui-dropdown > button')->text());
        self::assertSame(['Show', 'Delete'], $actions->filter('.crudit-ui-menu__text')->each(static fn (Crawler $item): string => $item->text()));
        self::assertSame('Delete “Chloé”?', $actions->filter('.crudit-ui-menu button')->attr('data-confirm-text'));

        self::assertCount(0, $crawler->filter('.crudit-row-actions')->filter('[data-bs-toggle], .btn, .dropdown-menu, i.fa'));
    }

    /**
     * The bar only: the dialog of the export keeps its select until its lot (3.1.g).
     */
    private function assertNoBootstrap(Crawler $crawler): void
    {
        self::assertCount(0, $crawler->filter('.crudit-page-actions')->filter('[data-bs-toggle], .btn, .dropdown-menu, i.fa'));
    }

    private function idOf(string $name): int
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $client = $em->getRepository(Client::class)->findOneBy(['name' => $name]);
        self::assertNotNull($client);

        return (int) $client->getId();
    }
}
