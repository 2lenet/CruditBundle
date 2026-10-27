<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;

/**
 * The global search of the layout (lot 2.2.c): the button of the header, the command palette, and the route that answers
 * it (the CrudConfig of `lle_crudit.search.entities`, then the screens of the menu).
 */
class SearchTest extends AppTestCase
{
    public function testButtonAndPalette(): void
    {
        $crawler = $this->client->request('GET', '/page/invoices');

        self::assertResponseIsSuccessful();
        $button = $crawler->filter('.crudit-shell__header button.crudit-search-trigger');
        self::assertCount(1, $button);
        self::assertSame('crudit-search', $button->attr('commandfor'));
        self::assertSame('show-modal', $button->attr('command'));
        self::assertSame('Control+K Meta+K', $button->attr('aria-keyshortcuts'));
        self::assertSame('Search', $button->filter('.crudit-search-trigger__label')->text());

        // Out of the header, so that Ctrl K works whatever the header of the project.
        $dialog = $crawler->filter('body > dialog#crudit-search');
        self::assertCount(1, $dialog);
        self::assertSame('/crudit/search', $dialog->attr('data-2lenet--crudit-ui-bundle--command-palette-url-value'));
        self::assertSame('Global search', $dialog->filter('h2')->text());
        self::assertSame('combobox', $dialog->filter('input')->attr('role'));
    }

    /**
     * @return array{groups: list<array{id: string, label: string, results: list<array<string, string|null>>}>}
     */
    private function search(string $text): array
    {
        $this->client->request('GET', '/crudit/search', ['q' => $text]);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/json');
        self::assertStringContainsString('no-store', (string) $this->client->getResponse()->headers->get('Cache-Control'));

        return json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }

    public function testEntityByNameAndByEmail(): void
    {
        $id = $this->idOf('Alice');

        $expected = [[
            'id' => 'CLIENT',
            'label' => 'Clients',
            'results' => [['id' => 'CLIENT-' . $id, 'title' => 'Alice', 'subtitle' => 'alice@example.org', 'url' => '/client/show/' . $id]],
        ]];
        self::assertSame($expected, $this->search('ali')['groups']);
        // The second search field of the datasource.
        self::assertSame($expected, $this->search('alice@')['groups']);
        self::assertSame($expected, $this->search(' ALI ')['groups']);
    }

    public function testScreens(): void
    {
        // The label of the menu, in the language of the request, without regard to the accents: the sub-menu is the
        // second line; the link of a role nobody has is not found.
        $groups = $this->search('INVOICES')['groups'];
        self::assertSame([['id' => 'screens', 'label' => 'Screens', 'results' => [['id' => 'screen-0', 'title' => 'Invoices', 'subtitle' => 'Billing', 'url' => '/page/invoices']]]], $groups);

        self::assertSame([], $this->search('secret')['groups']);
    }

    public function testEntitiesThenScreens(): void
    {
        // "cli" is in the label of the menu "Clients" and in the name of a client added here.
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist((new Client())->setName('Clinic')->setEmail('clinic@example.org'));
        $em->flush();

        $groups = $this->search('cli')['groups'];

        self::assertSame(['CLIENT', 'screens'], array_column($groups, 'id'));
        self::assertSame('Clinic', $groups[0]['results'][0]['title']);
        self::assertSame('Clients', $groups[1]['results'][0]['title']);
        self::assertSame('Screen', $groups[1]['results'][0]['subtitle']);
    }

    public function testNothingUnderTheMinimumNorWithoutResult(): void
    {
        self::assertSame([], $this->search('a')['groups']);
        self::assertSame([], $this->search('')['groups']);
        self::assertSame([], $this->search('zzzz')['groups']);
        // A very long text is cut, not refused.
        self::assertSame([], $this->search(str_repeat('x', 5000))['groups']);
    }

    public function testNoText(): void
    {
        $this->client->request('GET', '/crudit/search');

        self::assertResponseIsSuccessful();
        self::assertSame('{"groups":[]}', $this->client->getResponse()->getContent());
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
