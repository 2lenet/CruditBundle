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
