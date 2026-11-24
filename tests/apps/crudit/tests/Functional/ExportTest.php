<?php

declare(strict_types=1);

namespace App\Tests\Functional;

/**
 * The export of a list (lot 3.1.g): the dialog (ExportDialog of CruditUiBundle fed by `crudit_export()`), the file with
 * the cookie of the download, the CSV that Excel reads and the messages on the list. The database has 3 clients
 * (Alice, Bob, Chloé).
 */
class ExportTest extends AppTestCase
{
    public function testTheDialogOffersTheFormatsOfTheAction(): void
    {
        $crawler = $this->client->request('GET', '/client/');
        $dialog = $crawler->filter('.crudit-page-actions dialog[id^="modal_"], dialog[id^="modal_"]')->reduce(
            static fn ($node): bool => $node->filter('input[name=format]')->count() > 0
        )->first();

        self::assertCount(1, $dialog);
        self::assertSame('Export the list', trim($dialog->filter('h2')->text()));
        self::assertStringContainsString('all the results', $dialog->filter('.crudit-ui-dialog__description')->text());
        $form = $dialog->filter('form');
        self::assertSame('get', $form->attr('method'));
        self::assertSame('/client/export', $form->attr('action'));
        self::assertSame('false', $form->attr('data-turbo'));
        self::assertSame(['xls', 'csv', 'pdf'], $dialog->filter('input[name=format]')->each(static fn ($input): string => (string) $input->attr('value')));
        self::assertSame(['Excel', 'CSV', 'PDF'], $dialog->filter('.crudit-ui-export__title')->each(static fn ($title): string => trim($title->text())));
        self::assertSame('xls', $dialog->filter('input[name=format][checked]')->attr('value'));
        self::assertSame('Export', trim($dialog->filter('button[type=submit]')->text()));
    }

    public function testTheCsvHoldsAllTheResultsAndStartsWithAByteOrderMark(): void
    {
        // the page shown has one row: the file has the three
        $this->client->request('GET', '/client/?client_limit=1&client_offset=1');
        $this->client->request('GET', '/client/export?format=csv');

        self::assertResponseIsSuccessful();
        self::assertStringStartsWith('text/csv', (string) $this->client->getResponse()->headers->get('Content-Type'));
        $content = $this->client->getInternalResponse()->getContent();
        self::assertStringStartsWith("\xEF\xBB\xBF", $content);
        foreach (['Alice', 'Bob', 'Chloé'] as $name) {
            self::assertStringContainsString($name, $content);
        }
    }

    public function testAListOrAnEnumerationIsText(): void
    {
        $this->client->request('GET', '/client/export?format=csv');

        $content = $this->client->getInternalResponse()->getContent();
        // Alice has the tags "vip" and "north": joined by a comma, not "Array"
        self::assertStringContainsString('vip, north', $content);
        self::assertStringNotContainsString('Array', $content);
    }

    public function testTheCookieTellsThatTheDownloadBegins(): void
    {
        $this->client->request('GET', '/client/export?format=csv&download_token=0a1b2c3d-4e5f');

        $cookie = $this->client->getCookieJar()->get('crudit_export');
        self::assertNotNull($cookie);
        self::assertSame('0a1b2c3d-4e5f', $cookie->getValue());
        $header = null;
        foreach ($this->client->getResponse()->headers->getCookies() as $candidate) {
            if ($candidate->getName() === 'crudit_export') {
                $header = $candidate;
            }
        }
        self::assertNotNull($header);
        self::assertFalse($header->isHttpOnly(), 'the script of the dialog reads it');
        self::assertSame('lax', $header->getSameSite());
    }

    public function testNoCookieWithoutAValidToken(): void
    {
        $this->client->request('GET', '/client/export?format=csv');
        self::assertNull($this->client->getCookieJar()->get('crudit_export'));

        $this->client->request('GET', '/client/export?format=csv&download_token=a;b%20c');
        self::assertNull($this->client->getCookieJar()->get('crudit_export'), 'a token that is not a plain identifier is ignored');
    }

    public function testAnUnknownFormatIsAMessageOnTheList(): void
    {
        $this->client->request('GET', '/client/export?format=doc');

        self::assertResponseRedirects('/client/');
        $crawler = $this->client->followRedirect();
        self::assertStringContainsString('The export could not be generated.', $crawler->filter('.crudit-ui-toasts')->text());
    }

    public function testAFormatOfAProjectIsAcceptedWhereAnActionChoosesIt(): void
    {
        // the interventions have a second button, added with a numeric key, that offers `xml`: a project exporter
        $this->client->request('GET', '/intervention/export?format=xml');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('attachment', (string) $this->client->getResponse()->headers->get('Content-Disposition'));

        // not a format of the bundle, so the lists without such an action do not offer it
        $this->client->request('GET', '/client/export?format=xml');
        self::assertResponseRedirects('/client/');
    }

    public function testFromTheDialogAnErrorIsALightPageNotTheList(): void
    {
        $this->client->request('GET', '/client/export?format=doc&download_token=0a1b2c3d-4e5f');

        self::assertResponseStatusCodeSame(422);
        $crawler = $this->client->getCrawler();
        self::assertSame('The export could not be generated.', $crawler->filter('[data-crudit-export-error]')->text());
        self::assertCount(0, $crawler->filter('table'), 'the list is not rendered in the frame');
        self::assertNull($this->client->getCookieJar()->get('crudit_export'), 'no file, no cookie');
    }
}
