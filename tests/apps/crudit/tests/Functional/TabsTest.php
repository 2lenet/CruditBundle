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
        $heading = $section->filter('h2.crudit-ui-visually-hidden');
        self::assertSame('Related information', $heading->text());
        self::assertSame($heading->attr('id'), $section->attr('aria-labelledby'));

        $tabs = $section->filter('.crudit-ui-tabs');
        self::assertSame('tab', $tabs->attr('data-2lenet--crudit-ui-bundle--tabs-url-param-value'));
        self::assertSame('Related information', $tabs->filter('[role="tablist"]')->attr('aria-label'));
        // The tab with a role nobody has is not rendered; the counter after the label
        self::assertSame(['Tags 2', 'Notes'], $tabs->filter('[role="tab"]')->each(fn (Crawler $tab) => trim($tab->text())));
        self::assertSame(['true', 'false'], $tabs->filter('[role="tab"]')->each(fn (Crawler $tab) => (string) $tab->attr('aria-selected')));
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
        self::assertSame(['false', 'true'], $crawler->filter('.crudit-show-tabs [role="tab"]')->each(fn (Crawler $tab) => (string) $tab->attr('aria-selected')));
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

    public function testAfterADeleteFromASublistBackToTheTab(): void
    {
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
