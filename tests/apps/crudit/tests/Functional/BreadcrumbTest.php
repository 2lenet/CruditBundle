<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Lot 2.2.b: the breadcrumb computed from the menu (crudit_breadcrumb() on a page of the project, in a PageHeader), the
 * header of the CRUD pages (LinksBrick: PageHeader, breadcrumb of the record) and the <title> "Page – Application".
 */
class BreadcrumbTest extends AppTestCase
{
    public function testBreadcrumbFromTheMenu(): void
    {
        $crawler = $this->client->request('GET', '/page/invoices');

        // Home, the category, the sub-menu (texts), the entry: the current page, not a link
        $items = $crawler->filter('nav.crudit-ui-breadcrumb li')->each(static fn ($li) => [$li->text(), $li->filter('a')->count() ? $li->filter('a')->attr('href') : null]);
        self::assertSame([['Home', '/'], ['Operations', null], ['Billing', null], ['Invoices', null]], $items);
    }

    public function testEntryOfATitle(): void
    {
        $crawler = $this->client->request('GET', '/page/exports');

        // Administration and Tools are two titles: Tools is not under Administration
        self::assertSame(['Home', 'Tools', 'Exports'], $crawler->filter('nav.crudit-ui-breadcrumb li')->each(static fn ($li) => $li->text()));
    }

    public function testHomePage(): void
    {
        $crawler = $this->client->request('GET', '/');

        // One level: no breadcrumb (Breadcrumb of CruditUiBundle)
        self::assertCount(0, $crawler->filter('nav.crudit-ui-breadcrumb'));
        self::assertSame('Home', $crawler->filter('h1#page-title')->text());
    }

    public function testTitleOfTheCrudPages(): void
    {
        $this->client->request('GET', '/client/');
        self::assertSame('Clients – Gestio', $this->client->getCrawler()->filter('title')->text());

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $bob = $em->getRepository(Client::class)->findOneBy(['name' => 'Bob']);
        self::assertNotNull($bob);
        $this->client->request('GET', '/client/show/' . $bob->getId());
        self::assertResponseIsSuccessful();
        self::assertSame('Bob – Gestio', $this->client->getCrawler()->filter('title')->text());
    }

    public function testHeaderOfTheCrudPages(): void
    {
        $crawler = $this->client->request('GET', '/client/');
        self::assertSame('Clients', $crawler->filter('.crudit-ui-page-header h1#page-title')->text());
        self::assertCount(1, $crawler->filter('h1'));
        self::assertSame([['Home', '/'], ['Operations', null], ['Clients', null]], $this->crumbs($crawler));
        self::assertSame('page', $crawler->filter('nav.crudit-ui-breadcrumb [aria-current]')->attr('aria-current'));

        $id = $this->idOf('Bob');
        $crawler = $this->client->request('GET', '/client/show/' . $id);
        self::assertSame('Bob', $crawler->filter('h1#page-title')->text());
        self::assertSame([['Home', '/'], ['Operations', null], ['Clients', '/client/'], ['Bob', null]], $this->crumbs($crawler));

        $crawler = $this->client->request('GET', '/client/edit/' . $id);
        self::assertSame('Edit Bob', $crawler->filter('h1#page-title')->text());
        self::assertSame([['Home', '/'], ['Operations', null], ['Clients', '/client/'], ['Bob', '/client/show/' . $id], ['Edit', null]], $this->crumbs($crawler));

        $crawler = $this->client->request('GET', '/client/new');
        self::assertSame('New client', $crawler->filter('h1#page-title')->text());
        self::assertSame([['Home', '/'], ['Operations', null], ['Clients', '/client/'], ['New', null]], $this->crumbs($crawler));
    }

    /**
     * @return list<array{string, ?string}>
     */
    private function crumbs(Crawler $crawler): array
    {
        return $crawler->filter('nav.crudit-ui-breadcrumb li')->each(static fn (Crawler $li): array => [
            trim($li->text()),
            $li->filter('a')->count() > 0 ? $li->filter('a')->attr('href') : null,
        ]);
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
