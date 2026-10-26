<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;

/**
 * The CRUD screens of Crudit on the real container and a database: they are reworked in phase 3, these tests make
 * sure that they keep working while the layout changes, and give the later lots a place for their cases.
 */
class CrudTest extends AppTestCase
{
    public function testList(): void
    {
        $crawler = $this->client->request('GET', '/client/');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Alice', $crawler->filter('main')->text());
        self::assertStringContainsString('bob@example.org', $crawler->filter('main')->text());
    }

    public function testListIsATable(): void
    {
        $crawler = $this->client->request('GET', '/client/');

        self::assertCount(1, $crawler->filter('main table.crudit-ui-table__table'));
        self::assertCount(3, $crawler->filter('main tbody tr.crudit-ui-table__row'));
        self::assertNotSame('', trim($crawler->filter('main table caption')->text()), 'the table has a caption');
        self::assertCount(1, $crawler->filter('main th[aria-sort="ascending"]'), 'sorted by name by default');
        self::assertCount(3, $crawler->filter('main .crudit-ui-table__card'), 'the same rows as cards for the small screens');
    }

    public function testListTemplateCanBeExtendedToPutATextAboveTheTable(): void
    {
        $twig = static::getContainer()->get('twig');
        $template = $twig->createTemplate("{% extends '@LleCrudit/brick/list_items/index.html.twig' %}{% block before %}HELP{% endblock %}");

        self::assertTrue($template->hasBlock('before'));
        self::assertTrue($twig->load('@LleCrudit/brick/list_items/index.html.twig')->hasBlock('before'));
    }

    public function testListSortLinkAndOrder(): void
    {
        $crawler = $this->client->request('GET', '/client/?client_sort=name&client_sort_order=desc');

        self::assertSame(['Chloé', 'Bob', 'Alice'], $crawler->filter('main tbody tr.crudit-ui-table__row th[scope=row]')->each(fn ($th) => trim($th->text())));
        self::assertCount(1, $crawler->filter('main th[aria-sort="descending"]'));
        $link = $crawler->filter('main th[aria-sort="descending"] a')->attr('href');
        self::assertStringContainsString('client_sort_order=asc', $link, 'the link of the sorted column reverses the order');
    }

    public function testRowActions(): void
    {
        $id = $this->idOf('Alice');
        $crawler = $this->client->request('GET', '/client/');
        $row = $crawler->filter('main tr#list-client-' . $id);

        self::assertSame('/client/edit/' . $id, $row->filter('.crudit-ui-table__action-list a[href]')->first()->attr('href'), 'the edit action is the icon');
        self::assertStringContainsString('Alice', $row->filter('.crudit-ui-table__action-list')->text(), 'named after the row');
        self::assertSame('/client/delete/' . $id, $row->filter('.crudit-ui-table__action-list [data-confirm-link]')->attr('data-confirm-link'), 'the deletion asks for a confirmation');
    }

    public function testShow(): void
    {
        $id = $this->idOf('Bob');
        $crawler = $this->client->request('GET', '/client/show/' . $id);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('bob@example.org', $crawler->filter('main')->text());
    }

    public function testNewAndEditForms(): void
    {
        $this->client->request('GET', '/client/new');
        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/client/edit/' . $this->idOf('Alice'));
        self::assertResponseIsSuccessful();
    }

    public function testCreate(): void
    {
        $crawler = $this->client->request('GET', '/client/new');
        $form = $crawler->filter('main form')->form();
        $form->setValues(['client[name]' => 'Dora', 'client[email]' => 'dora@example.org']);
        $this->client->submit($form);

        self::assertResponseRedirects();
        self::assertNotNull($this->findByName('Dora'));
    }

    public function testNotFound(): void
    {
        $this->client->request('GET', '/client/show/9999');

        self::assertTrue($this->client->getResponse()->isClientError() || $this->client->getResponse()->isRedirection());
    }

    private function findByName(string $name): ?Client
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        return $em->getRepository(Client::class)->findOneBy(['name' => $name]);
    }

    private function idOf(string $name): int
    {
        $client = $this->findByName($name);
        self::assertNotNull($client);

        return (int) $client->getId();
    }
}
