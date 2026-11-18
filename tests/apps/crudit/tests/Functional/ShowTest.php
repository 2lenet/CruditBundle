<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Client;
use App\Entity\Intervention;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * The record (lot 3.3.a): the status, the subtitle and the dates in the header, an alert, the key information, the
 * sections (a button in a <h2>, open or folded), their grid and the fields of no group in a card without title.
 */
class ShowTest extends AppTestCase
{
    private int $late;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist($late = (new Intervention())
            ->setReference('INT-LATE')
            ->setTechnician('Camille Martin')
            ->setStatus('planned')
            ->setPlannedOn(new \DateTime('today -5 days'))
            ->setAmount('450.00')
            ->setDescription("Replace the boiler.\nThe code is in the file.")
            ->setInternalNote('Secret')
            ->setCreatedAt(new \DateTime('2026-11-03 14:05:00'))
            ->setUpdatedAt(new \DateTime('2026-11-10 09:12:00'))
            ->setUpdatedBy('Léa <b>Martin</b>'));
        $em->flush();
        $this->late = (int) $late->getId();
    }

    public function testTheHeaderOfTheRecord(): void
    {
        $crawler = $this->client->request('GET', '/intervention/show/' . $this->late);
        self::assertResponseIsSuccessful();

        $header = $crawler->filter('main .crudit-page-header');
        self::assertCount(1, $crawler->filter('h1'));
        self::assertSame('INT-LATE', trim($header->filter('h1')->text()));
        // The status beside the title, with its transitions; no more in its section
        self::assertSame('Planned, change the status of INT-LATE', $header->filter('.crudit-ui-page-header__title-row .crudit-value--workflow button.crudit-workflow__trigger')->text());
        self::assertCount(0, $crawler->filter('.crudit-show [id$="-status"]'));
        self::assertCount(1, $crawler->filter('.crudit-value--workflow'), 'the status is shown once');
        // The subtitle and the dates, the author escaped
        self::assertSame('Camille Martin', $header->filter('.crudit-ui-page-header__subtitle')->text());
        $meta = $header->filter('.crudit-ui-page-header__meta .crudit-record-meta');
        // ICU puts a narrow no-break space before AM / PM
        self::assertSame("Created on Nov 3, 2026, 2:05\u{202F}PM · modified on Nov 10, 2026, 9:12\u{202F}AM by Léa <b>Martin</b>", $meta->text());
        self::assertSame(['2026-11-03T14:05:00+00:00', '2026-11-10T09:12:00+00:00'], $meta->filter('time')->each(static fn (Crawler $time): string => (string) $time->attr('datetime')));
        self::assertCount(0, $meta->filter('b'));
    }

    public function testTheAlertAndTheKeyInformation(): void
    {
        $crawler = $this->client->request('GET', '/intervention/show/' . $this->late);

        $alert = $crawler->filter('.crudit-show > .crudit-ui-alert.crudit-ui-alert--warning');
        self::assertSame('Warning: Late: the planned date is past by 5 days.', trim($alert->text()));

        $highlights = $crawler->filter('.crudit-show > dl.crudit-show-highlights > .crudit-show-highlights__item');
        self::assertSame(['Planned on', 'Technician', 'Amount'], $highlights->filter('dt')->each(static fn (Crawler $dt): string => $dt->text()));
        self::assertStringContainsString('450.00', $highlights->eq(2)->filter('dd')->text());

        // Neither alert nor band when the record has none
        $this->client->request('GET', '/client/show/' . $this->idOf('Bob'));
        self::assertSelectorNotExists('.crudit-show .crudit-ui-alert');
        self::assertSelectorNotExists('.crudit-show-highlights');
    }

    public function testTheSections(): void
    {
        $crawler = $this->client->request('GET', '/intervention/show/' . $this->late);

        $sections = $crawler->filter('.crudit-show-sections.crudit-show-sections--2 > .crudit-show-section');
        // The section whose fields are all refused is not shown
        self::assertSame(['Intervention', 'Billing', 'History'], $sections->filter('h2 > button')->each(static fn (Crawler $button): string => trim($button->text())));
        self::assertCount(0, $crawler->filter('[id$="-internalnote"]'));
        // Open, then folded after getShowNumberFieldGroupsOpened() (3): the refused section counts
        self::assertSame(['true', 'true', 'false'], $sections->filter('h2 > button')->each(static fn (Crawler $button): string => (string) $button->attr('aria-expanded')));
        self::assertSame('crudit-show-intervention-section-billing-panel', $sections->eq(1)->filter('h2 > button')->attr('aria-controls'));
        self::assertCount(1, $sections->eq(2)->filter('.crudit-ui-disclosure__panel--closed'));
        // Two columns as soon as there are 2 sections; the history alone on its line (getShowWideSections())
        self::assertSame([false, false, true], $sections->each(static fn (Crawler $section): bool => str_contains((string) $section->attr('class'), 'crudit-show-section--wide')));

        // The grid: 4 columns, 2 in the section of billing; the technician over 2 columns, a long text the whole line
        self::assertSame(['crudit-show-grid--4', 'crudit-show-grid--2', 'crudit-show-grid--4'], $sections->filter('dl.crudit-show-grid')->each(static fn (Crawler $dl): string => (string) preg_replace('/^.*(crudit-show-grid--\d).*$/', '$1', (string) $dl->attr('class'))));
        $id = 'show-intervention-' . $this->late . '-';
        self::assertStringContainsString('crudit-show-field--span-2', (string) $crawler->filter('dd#' . $id . 'technician')->ancestors()->first()->attr('class'));
        self::assertStringContainsString('crudit-show-field--span-full', (string) $crawler->filter('dd#' . $id . 'description')->ancestors()->first()->attr('class'));
        // The label above the value, in a <dl>
        $field = $crawler->filter('dd#' . $id . 'reference')->ancestors()->first();
        self::assertSame(['dt', 'dd'], $field->children()->each(static fn (Crawler $node): string => $node->nodeName()));
        self::assertSame('Reference', $field->filter('dt')->text());
    }

    public function testTheFieldsOfNoGroupAreACardWithoutTitle(): void
    {
        $crawler = $this->client->request('GET', '/client/show/' . $this->idOf('Alice'));

        // One section: one column, even on a wide screen
        $sections = $crawler->filter('.crudit-show-sections.crudit-show-sections--1 > .crudit-show-section');
        self::assertCount(1, $sections);
        self::assertCount(0, $sections->filter('h2, button.crudit-ui-disclosure__toggle'));
        self::assertSame('Alice', trim($crawler->filter('dd#show-client-' . $this->idOf('Alice') . '-name')->text()));
        // A long text (Markdown) takes the whole line
        self::assertStringContainsString('crudit-show-field--span-full', (string) $crawler->filter('dd#show-client-' . $this->idOf('Alice') . '-notes')->ancestors()->first()->attr('class'));
        // No workflow: no status in the header; the creation only, without author
        self::assertSelectorNotExists('.crudit-page-header .crudit-value--workflow');
        self::assertMatchesRegularExpression('/^Created on \w{3} \d{1,2}, \d{4}, \d{1,2}:\d{2}\x{202F}[AP]M$/u', $crawler->filter('.crudit-record-meta')->text());
    }

    public function testTheFormsHaveNoHeaderOfRecord(): void
    {
        // A client has a creation date: not in the header of its form
        $crawler = $this->client->request('GET', '/client/edit/' . $this->idOf('Alice'));
        self::assertResponseIsSuccessful();

        self::assertCount(0, $crawler->filter('.crudit-record-meta, .crudit-ui-page-header__subtitle'));
    }

    private function idOf(string $name): int
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        return (int) $em->getRepository(Client::class)->findOneBy(['name' => $name])?->getId();
    }
}
