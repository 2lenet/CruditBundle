<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\FieldView;
use Lle\CruditBundle\Field\TextAreaField;
use Lle\CruditBundle\Registry\FieldRegistry;
use Symfony\Component\DomCrawler\Crawler;

/**
 * The values of the fields (lot 3.4.a), in a cell of the list, a card and the record: one field of each kind, all
 * filled for Alice, some for Bob, none for Chloé. No Bootstrap, no id in a value (a cell is rendered twice), an
 * empty value is a dash read as "Not set".
 */
class FieldsTest extends AppTestCase
{
    public function testTheValuesOfTheRecord(): void
    {
        $show = $this->show('Alice');

        self::assertSame('alice.example/shop (new tab)', trim($this->value($show, 'website')->text()));
        // The host, then the path (hidden in a cell of a table)
        self::assertSame(['alice.example', '/shop'], [$this->value($show, 'website')->filter('.crudit-value__host')->text(), $this->value($show, 'website')->filter('.crudit-value__path')->text()]);
        $link = $this->value($show, 'website')->filter('a');
        // A scheme in capitals is a web address too
        self::assertSame(['HTTPS://WWW.alice.example/shop', '_blank', 'noopener noreferrer'], [$link->attr('href'), $link->attr('target'), $link->attr('rel')]);
        $phone = $this->value($show, 'phone')->filter('a');
        self::assertSame(['tel:+33612345678', 'ltr'], [$phone->attr('href'), $phone->attr('dir')]);
        self::assertSame('mailto:alice@example.org', $this->value($show, 'email')->filter('a')->attr('href'));
        self::assertSame("€1,234.50", trim($this->value($show, 'amount')->text()));
        self::assertSame(date('Y-m-d'), $this->value($show, 'createdat')->filter('time')->attr('datetime'));
        self::assertSame('#0077a8', $this->value($show, 'color')->filter('code')->text());

        // The label of the choice, translated, in a Status of its tone
        $status = $this->value($show, 'status')->filter('.crudit-ui-status');
        self::assertSame(['Customer', 'crudit-ui-status crudit-ui-tone--success crudit-ui-status--small'], [trim($status->text()), $status->attr('class')]);

        // Yes is the bad news (reverseColors): the tones are swapped, the text stays
        $blocked = $this->value($show, 'blocked')->filter('.crudit-ui-bool');
        self::assertSame(['Yes', 'crudit-ui-bool crudit-ui-bool--yes crudit-ui-bool--reverse'], [trim($blocked->text()), $blocked->attr('class')]);

        self::assertSame(['vip', 'north'], $this->value($show, 'tags')->filter('ul li')->each(static fn (Crawler $item): string => $item->text()));
        self::assertSame('Bob', trim($this->value($show, 'referrer')->text()));

        // A backed enum: its label and the tone of its value; several values: their labels, separated by commas
        $priority = $this->value($show, 'priority')->filter('.crudit-ui-status');
        self::assertSame(['High', 'crudit-ui-status crudit-ui-tone--danger crudit-ui-status--small'], [trim($priority->text()), $priority->attr('class')]);
        self::assertSame('E-mail, Phone', trim($this->value($show, 'channels')->text()));
        self::assertSame('Phone', trim($this->value($show, 'channels')->filter('.crudit-ui-tone--info')->text()));
    }

    public function testAJsonObjectWithAnEmptyList(): void
    {
        $tags = $this->value($this->show('Bob'), 'tags');

        self::assertSame('div', $tags->nodeName(), 'a list in a block');
        self::assertSame(['since', 'source', 'visits'], $tags->filter('dt')->each(static fn (Crawler $dt): string => $dt->text()));
        self::assertSame('—Not set', trim($tags->filter('dd')->last()->text()));
    }

    public function testTheProgress(): void
    {
        $show = $this->show('Alice');

        $progress = $this->value($show, 'progress')->filter('progress');
        self::assertSame(['45', '100', '45%', 'field.progress'], [$progress->attr('value'), $progress->attr('max'), $progress->attr('aria-valuetext'), $progress->attr('aria-label')]);
        self::assertStringContainsString('crudit-ui-progress--primary', (string) $this->value($show, 'progress')->filter('.crudit-value__progress')->attr('class'));

        // Above max: an alert by default (overMax danger, as in 1.x), the bar stays full, the real value is written
        $budget = $this->value($show, 'budgetused');
        self::assertSame(['100', '130%'], [$budget->filter('progress')->attr('value'), $budget->filter('progress')->attr('aria-valuetext')]);
        self::assertStringContainsString('crudit-ui-progress--danger', (string) $budget->filter('.crudit-value__progress')->attr('class'));

        // At max: success; above max with overMax success: full, as at max
        self::assertStringContainsString('crudit-ui-progress--success', (string) $this->value($this->show('Bob'), 'progress')->filter('.crudit-value__progress')->attr('class'));
        self::assertStringContainsString('crudit-ui-progress--success', (string) $this->render(Field::new('budget', 'progress', ['overMax' => 'success']), 130)->filter('.crudit-value__progress')->attr('class'));
    }

    public function testTheWebAddressesAreShownClean(): void
    {
        // The user and the password are not shown; the title gives the address shown whole
        $credentials = $this->render(Field::new('site', 'url'), 'https://user:secret@www.example.org/shop?page=2');
        self::assertSame(['example.org', '/shop?page=2'], [$credentials->filter('.crudit-value__host')->text(), $credentials->filter('.crudit-value__path')->text()]);
        self::assertSame('example.org/shop?page=2', $credentials->filter('a')->attr('title'));
        self::assertStringNotContainsString('secret', $credentials->text());

        // A query without a path is not in the host
        $query = $this->render(Field::new('site', 'url'), 'https://example.org?x=1');
        self::assertSame(['example.org', '?x=1'], [$query->filter('.crudit-value__host')->text(), $query->filter('.crudit-value__path')->text()]);

        // Without a path, no title: the text shown is the whole address
        self::assertNull($this->render(Field::new('site', 'url'), 'https://www.example.org')->filter('a')->attr('title'));

        // Without ://, shown as it is, not cut at the @
        foreach (['jean@example.org', 'mailto:jean@example.org'] as $address) {
            $text = $this->render(Field::new('site', 'url'), $address);
            self::assertSame($address, trim($text->text()));
            self::assertCount(0, $text->filter('a'));
        }
    }

    public function testValuesOfEdgeCases(): void
    {
        // A JSON decoded as objects
        $json = $this->render(Field::new('tags', 'json'), json_decode('{"since": 2024, "visits": []}'));
        self::assertSame(['since', 'visits'], $json->filter('dt')->each(static fn (Crawler $dt): string => $dt->text()));

        // A list of objects: converted at every depth
        $list = $this->render(Field::new('tags', 'json'), json_decode('[{"a": 1}, {"b": 2}]'));
        self::assertSame(['a', 'b'], $list->filter('ul > li dt')->each(static fn (Crawler $dt): string => $dt->text()));

        // An empty amount in cents
        self::assertSame('—Not set', trim($this->render(Field::new('amount', 'currency', ['isInt' => true]), '')->text()));

        // A long text gives its whole text as title (it may be clamped in a cell)
        self::assertSame("A\nlong text", $this->render(Field::new('notes', TextAreaField::class), "A\nlong text")->filter('.crudit-value')->attr('title'));
    }

    public function testTheRichTextIsCleaned(): void
    {
        $notes = $this->value($this->show('Alice'), 'notes');

        self::assertSame('Notes', $notes->filter('h4')->text(), 'the headings start at h4, under those of the page');
        self::assertCount(0, $notes->filter('h1, script'));
        self::assertSame('good', $notes->filter('strong')->text());
    }

    public function testAnAddressThatIsNotAWebPageIsNoLink(): void
    {
        $website = $this->value($this->show('Bob'), 'website');

        self::assertCount(0, $website->filter('a'));
        self::assertSame('javascript:alert(1)', trim($website->text()));
    }

    public function testAnEmptyValueIsReadNotSet(): void
    {
        $show = $this->show('Chloé');

        foreach (['amount', 'progress', 'status', 'notes', 'phone', 'website', 'color', 'blocked', 'tags', 'referrer', 'priority', 'channels'] as $field) {
            self::assertSame('—Not set', trim($this->value($show, $field)->filter('.crudit-ui-empty-value')->text()), $field);
        }
    }

    public function testTheCellsOfTheList(): void
    {
        $crawler = $this->client->request('GET', '/client/');
        $id = $this->idOf('Alice');

        // The amounts at the end, aligned by their field; the text at the start
        self::assertStringContainsString('crudit-ui-table__cell--end', (string) $crawler->filter('#list-client-' . $id . '-amount-amount')->attr('class'));
        self::assertStringNotContainsString('crudit-ui-table__cell--end', (string) $crawler->filter('#list-client-' . $id . '-email-email')->attr('class'));
        // A long text is clamped in a cell, whole in the card
        self::assertCount(1, $crawler->filter('#list-client-' . $id . '-notes-notes .crudit-value--clamp'));

        $main = $crawler->filter('main');
        self::assertCount(0, $main->filter('.crudit-ui-table__value [id], .crudit-ui-table__card-fields [id]'), 'no id in a value: the cell is rendered twice');
        self::assertCount(0, $main->filter('.crudit-value .text-end, .crudit-value .me-3, .crudit-value .badge, .crudit-value .progress-bar, .crudit-value i.fa'), 'no Bootstrap nor Font Awesome');
    }

    /**
     * A value rendered by its field, outside a page.
     */
    private function render(Field $field, mixed $value): Crawler
    {
        /** @var FieldRegistry $fields */
        $fields = static::getContainer()->get(FieldRegistry::class);
        $view = $fields->get((string) $field->getType())->buildView(new FieldView($field, $value), $value);

        return (new Crawler('<div>' . $view->getValue() . '</div>'))->filter('div')->first();
    }

    private function show(string $name): Crawler
    {
        return $this->client->request('GET', '/client/show/' . $this->idOf($name));
    }

    private function value(Crawler $show, string $field): Crawler
    {
        $value = $show->filter('[id$="-' . $field . '"] .crudit-value');
        self::assertCount(1, $value, $field);

        return $value;
    }

    private function idOf(string $name): int
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        return (int) $em->getRepository(Client::class)->findOneBy(['name' => $name])?->getId();
    }
}
