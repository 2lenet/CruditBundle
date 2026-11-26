<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Controller\ClientController;
use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

/**
 * The tabs of a record (lot 3.3.b): the component Tabs of CruditUiBundle under the sections, only the tabs granted, a
 * counter, the tab in the address (?tab=<id>), a form that comes back to its tab once saved.
 */
class TabsTest extends AppTestCase
{
    public function testTheTabsOfTheRecord(): void
    {
        $crawler = $this->client->request('GET', '/client/show/' . $this->idOf('Alice'));
        self::assertResponseIsSuccessful();

        $section = $crawler->filter('main section.crudit-show-tabs');
        self::assertCount(1, $section);
        self::assertSame('Related information', $section->filter('h2.crudit-ui-visually-hidden')->text());
        // Not a named region: "Related information" would be read a third time, after the heading and the tablist
        self::assertNull($section->attr('aria-labelledby'));
        self::assertNull($section->attr('aria-label'));

        $tabs = $section->filter('.crudit-ui-tabs');
        self::assertSame('tab', $tabs->attr('data-2lenet--crudit-ui-bundle--tabs-url-param-value'));
        self::assertSame('Related information', $tabs->filter('[role="tablist"]')->attr('aria-label'));
        // The tab with a role nobody has is not rendered; the counter after the label
        self::assertSame(['Tags 2', 'Notes', 'Referred'], $tabs->filter('[role="tab"]')->each(fn (Crawler $tab) => trim($tab->text())));
        self::assertSame(['true', 'false', 'false'], $tabs->filter('[role="tab"]')->each(fn (Crawler $tab) => (string) $tab->attr('aria-selected')));
        $id = (string) $tabs->attr('id');
        self::assertSame($id . '-panel-tags', $tabs->filter('[role="tab"]')->first()->attr('aria-controls'));
        // All the panels in the page, the selected one shown: the free content, the form
        self::assertSame(['vip', 'north'], $tabs->filter('#' . $id . '-panel-tags li')->each(fn (Crawler $tag) => $tag->text()));
        self::assertNotNull($tabs->filter('#' . $id . '-panel-notes')->attr('hidden'));
        self::assertCount(1, $tabs->filter('#' . $id . '-panel-notes form textarea[name="client_notes[notes]"]'));
        self::assertStringNotContainsString('Accounting', (string) $section->html());
        // No Bootstrap left
        self::assertCount(0, $crawler->filter('.nav-tabs, [data-bs-toggle="tab"]'));
    }

    public function testTheTabOfTheAddress(): void
    {
        $id = $this->idOf('Bob');
        $crawler = $this->client->request('GET', '/client/show/' . $id . '?tab=notes');
        self::assertSame(['false', 'true', 'false'], $crawler->filter('.crudit-show-tabs [role="tab"]')->each(fn (Crawler $tab) => (string) $tab->attr('aria-selected')));
        self::assertSame('Tags 3', trim($crawler->filter('.crudit-show-tabs [role="tab"]')->first()->text()));

        // A tab not granted, unknown, or an array in the address: the first one, no error
        foreach (['?tab=accounting', '?tab=nope', '?tab[]=notes'] as $query) {
            $crawler = $this->client->request('GET', '/client/show/' . $id . $query);
            self::assertResponseIsSuccessful();
            self::assertSame('true', $crawler->filter('.crudit-show-tabs [role="tab"]')->first()->attr('aria-selected'), $query);
        }
    }

    public function testAFormInATabComesBackToItsTab(): void
    {
        $id = $this->idOf('Chloé');
        $crawler = $this->client->request('GET', '/client/show/' . $id);
        $form = $crawler->filter('.crudit-show-tabs [role="tabpanel"] form')->form();
        $form->setValues(['client_notes[notes]' => 'Call back in March.']);
        $this->client->submit($form);

        self::assertResponseRedirects('/client/show/' . $id . '?tab=notes');
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        self::assertSame('Call back in March.', $em->find(Client::class, $id)?->getNotes());
    }

    public function testTheLinksOfASublistInATabKeepTheTab(): void
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $bob = $em->getRepository(Client::class)->findOneBy(['name' => 'Bob']);
        self::assertNotNull($bob);
        // Alice and 3 more referred by Bob: 2 pages of 2 (client_limit, as the list)
        foreach (['Dan', 'Eva', 'Fred'] as $name) {
            $em->persist((new Client())->setName($name)->setEmail(strtolower($name) . '@example.org')->setActive(false)->setReferrer($bob));
        }
        $em->flush();

        $crawler = $this->client->request('GET', '/client/show/' . $bob->getId() . '?tab=referred&client_limit=2');
        self::assertResponseIsSuccessful();
        $panel = $crawler->filter('.crudit-show-tabs [role="tabpanel"]')->reduce(fn (Crawler $panel) => str_ends_with((string) $panel->attr('id'), '-panel-referred'));
        self::assertNull($panel->attr('hidden'), 'the tab of the address is shown');
        $links = array_merge(
            $panel->filter('.pagination a')->each(fn (Crawler $a) => (string) $a->attr('href')),
            $panel->filter('thead a')->each(fn (Crawler $a) => (string) $a->attr('href')),
        );
        self::assertNotEmpty($panel->filter('.pagination a'), 'a pager: 4 clients, 2 per page');
        self::assertNotEmpty($panel->filter('thead a'), 'the sorting of the columns');
        foreach ($links as $href) {
            parse_str((string) parse_url($href, \PHP_URL_QUERY), $query);
            self::assertSame('referred', $query['tab'] ?? null, $href);
            self::assertStringNotContainsString('#', $href);
        }
    }

    public function testAfterADeleteFromASublistBackToTheTab(): void
    {
        // By reflection: TraitCrudController::delete() goes back to the record only for the routes app_crudit_*_delete
        // (controllers in App\Controller\Crudit, as in the projects) and for another entity than the one deleted; the
        // test application has app_client_delete and a sublist of clients in a client.
        $withTabOf = new \ReflectionMethod(ClientController::class, 'withTabOf');
        $request = static fn (string $referer): Request => new Request(server: ['HTTP_REFERER' => $referer]);

        // The tab of the page sent from, on the same record
        self::assertSame('/client/show/1?tab=contacts', $withTabOf->invoke(null, $request('http://localhost/client/show/1?tab=contacts'), '/client/show/1'));
        self::assertSame('/client/show/1?page=2&tab=contacts', $withTabOf->invoke(null, $request('http://localhost/client/show/1?tab=contacts'), '/client/show/1?page=2&tab=old'));
        // Another page, no tab, or an array: the address kept
        self::assertSame('/client/show/1', $withTabOf->invoke(null, $request('http://localhost/client/show/2?tab=contacts'), '/client/show/1'));
        self::assertSame('/client/show/1', $withTabOf->invoke(null, $request('http://localhost/client/show/1'), '/client/show/1'));
        self::assertSame('/client/show/1', $withTabOf->invoke(null, $request('http://localhost/client/show/1?tab[]=x'), '/client/show/1'));
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
