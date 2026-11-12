<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Component\DomCrawler\Crawler;

/**
 * The form of a CRUD (lot 3.2.a), with bootstrap_5_layout as the global theme of the application (as 24 projects):
 * the theme of Crudit is imposed. Labels above and attached, required fields explained, help and errors attached,
 * sections with their own columns, widths of the fields, a switch, choices shown as boxes, a unit. The base types (lot
 * 3.2.b): keyboards of a phone, autofill kept away from the inputs typed in only, counter of characters, format helpers,
 * telephone, colour, segmented control, date in lists, a boolean guessed as a switch.
 */
class FormTest extends AppTestCase
{
    public function testTheThemeOfCrudit(): void
    {
        $form = $this->form();

        self::assertCount(0, $form->filter('.form-floating, .form-control, .form-select, .form-check, .mb-3, .row, .card'), 'no Bootstrap');
        self::assertSame("The fields marked with an asterisk (*) are required.", $form->filter('.crudit-form__legend')->text());

        // A required field: label above pointing to it, a star hidden from assistive technologies, its help attached
        $row = $form->filter('.crudit-ui-field')->reduce(fn (Crawler $field) => $field->filter('#client_name')->count() > 0);
        $label = $row->filter('.crudit-ui-field__label-row > label');
        self::assertSame('client_name', $label->attr('for'));
        self::assertSame('true', $label->filter('.crudit-ui-required')->attr('aria-hidden'));
        $input = $row->filter('#client_name');
        self::assertSame('required', $input->attr('required'));
        self::assertSame('client_name_help', $input->attr('aria-describedby'));
        self::assertSame('As on the invoice', $form->filter('#client_name_help')->text());
        self::assertSame('crudit-ui-input', $input->attr('class'));
        // As in the 1.x: no autofill of the browser, unless the field sets its own autocomplete
        self::assertSame('off', $form->attr('autocomplete'));
        self::assertSame('new-password', $input->attr('autocomplete'));
        self::assertSame('email', $form->filter('#client_email')->attr('autocomplete'));

        // The tooltip: a button next to the label that opens the help
        $tip = $row->filter('.crudit-ui-field__label-row button.crudit-ui-toggletip');
        self::assertSame('client_name_tooltip', $tip->attr('popovertarget'));
        // Named after the label of the field (field.name: the label of the CRUD, not translated in this application)
        self::assertSame('Help on the field ' . trim(str_replace("\u{a0}*", '', $label->text())), $tip->text());
        self::assertSame('The name of the company, or of the person', trim($form->filter('#client_name_tooltip')->text()));
    }

    public function testTheHtmlOfATooltipIsCleaned(): void
    {
        $tip = $this->form()->filter('#client_email_tooltip');

        self::assertSame('Work', $tip->filter('strong')->text());
        self::assertSame('only', $tip->filter('em')->text());
        self::assertNull($tip->filter('em')->attr('onclick'));
        self::assertCount(0, $tip->filter('script'));
        self::assertStringNotContainsString('alert', $tip->html());
    }

    public function testSectionsColumnsAndWidths(): void
    {
        $form = $this->form();

        $sections = $form->filter('fieldset.crudit-form-section');
        self::assertSame(['Identity', 'Contact', 'Details'], $sections->each(fn (Crawler $section) => $section->filter('legend h2')->text()));
        self::assertSame('How to reach the client', $sections->eq(1)->filter('.crudit-form-section__description')->text());
        // The columns of the form (lle_crudit.form_columns: 4), then those of the section
        self::assertSame('crudit-form-grid crudit-form-grid--4', $sections->eq(0)->filter('.crudit-form-grid')->attr('class'));
        self::assertSame('crudit-form-grid crudit-form-grid--2', $sections->eq(1)->filter('.crudit-form-grid')->attr('class'));
        // The fields of a section up to the next one, in its grid
        $grid = $sections->eq(0)->filter('.crudit-form-grid > .crudit-ui-field');
        self::assertCount(3, $grid);
        self::assertSame(1, $grid->eq(0)->filter('#client_name')->count());
        self::assertSame(1, $grid->eq(2)->filter('#client_progress')->count());

        self::assertStringContainsString('crudit-form-field--span-2', (string) $form->filter('#client_email')->closest('.crudit-ui-field')?->attr('class'));
        self::assertStringContainsString('crudit-form-field--span-3', (string) $form->filter('#client_progress')->closest('.crudit-ui-field')?->attr('class'));
        self::assertStringContainsString('crudit-form-field--span-full', (string) $form->filter('#client_notes')->closest('.crudit-ui-field')?->attr('class'));
    }

    public function testInputs(): void
    {
        $form = $this->form();

        // A switch: its label above, a native checkbox with the role switch, its state written
        $switch = $form->filter('#client_active');
        self::assertSame(['checkbox', 'switch'], [$switch->attr('type'), $switch->attr('role')]);
        self::assertSame('client_active', $form->filter('label[for="client_active"].crudit-ui-label')->attr('for'));

        // Choices shown as boxes: a fieldset named by its legend
        $channels = $form->filter('fieldset.crudit-ui-field__group')->reduce(fn (Crawler $set) => $set->filter('#client_channels')->count() > 0);
        self::assertSame('Channels', $channels->filter('legend')->text());
        self::assertCount(2, $channels->filter('ul.crudit-ui-check-list label.crudit-ui-check input[type="checkbox"]'));

        // A unit: next to the input, hidden from assistive technologies
        $unit = $form->filter('#client_progress')->closest('.crudit-ui-input-group')?->filter('.crudit-ui-input-group__unit');
        self::assertSame(['%', 'true'], [$unit?->text(), $unit?->attr('aria-hidden')]);

        // The buttons of the design system
        self::assertSame(['Cancel', 'Save'], $form->filter('.crudit-form__actions .crudit-ui-btn')->each(fn (Crawler $button) => trim($button->text())));
    }

    public function testTheKeyboardOfAPhoneAndTheFormatHelpers(): void
    {
        $form = $this->form();

        $modes = ['client_email' => 'email', 'client_amount' => 'decimal', 'client_progress' => 'decimal', 'client_phone_number' => 'tel'];
        foreach ($modes as $id => $mode) {
            self::assertSame($mode, $form->filter('#' . $id)->attr('inputmode'), $id);
        }
        // The type gives the keyboard of a URL (CruditUiBundle sets no inputmode on it)
        self::assertSame(['url', null], [$form->filter('#client_website')->attr('type'), $form->filter('#client_website')->attr('inputmode')]);
        $email = $form->filter('#client_email');
        self::assertSame('false', $email->attr('spellcheck'));
        self::assertSame('2lenet--crudit-bundle--input-format', $email->attr('data-controller'), 'crudit-email-format');
        self::assertStringContainsString('2lenet--crudit-bundle--input-format', (string) $form->filter('#client_website')->attr('data-controller'), 'crudit-lower-format');
        self::assertNull($form->filter('#client_name')->attr('data-controller'));
    }

    public function testTheAutofillIsKeptAwayFromTheInputsTypedInOnly(): void
    {
        $form = $this->form();

        foreach (['client_name', 'client_notes', 'client_amount', 'client_progress', 'client_website'] as $id) {
            self::assertSame('new-password', $form->filter('#' . $id)->attr('autocomplete'), $id);
        }
        self::assertSame('tel-national', $form->filter('#client_phone_number')->attr('autocomplete'), 'the purpose of the number');
        foreach (['#client_active', '#client_blocked', '#client_color', '#client_channels input', '#client_priority input', '#client_createdAt select', '#client_phone_country'] as $selector) {
            self::assertSame([null], array_unique($form->filter($selector)->each(fn (Crawler $input) => $input->attr('autocomplete'))), $selector);
        }
    }

    public function testACounterOfCharacters(): void
    {
        $form = $this->form();

        $notes = $form->filter('#client_notes');
        self::assertSame('client_notes_count', $notes->attr('aria-describedby'));
        self::assertSame('2lenet--crudit-ui-bundle--char-count', $notes->attr('data-controller'));
        self::assertSame('0 / 80 characters', $form->filter('#client_notes_count [data-crudit-ui-count]')->text());
        self::assertSame('120', $form->filter('#client_name')->attr('maxlength'), 'the length of the column, from Symfony');
        self::assertCount(0, $form->filter('#client_name_count'), 'a text on one line has no counter by default');
        self::assertSame('0 / 255 characters', $form->filter('#client_website_count [data-crudit-ui-count]')->text(), 'asked with counter: true');
    }

    public function testTheBaseTypes(): void
    {
        $form = $this->form();

        // A boolean guessed is a switch, unless the field says switch: false
        self::assertSame('switch', $form->filter('#client_active')->attr('role'));
        self::assertNull($form->filter('#client_blocked')->attr('role'));
        self::assertCount(1, $form->filter('label.crudit-ui-check:has(#client_blocked)'));

        // The telephone: the calling code and the number on one line, no Bootstrap
        $phone = $form->filter('#client_phone');
        self::assertSame('crudit-ui-input-group crudit-telephone', $phone->attr('class'));
        self::assertCount(0, $phone->filter('.input-group, .form-select, .form-control, .is-invalid'));
        self::assertSame('client_phone_number', $form->filter('label[for="client_phone_number"]')->attr('for'));
        self::assertSame('+33', $phone->filter('.crudit-telephone-code')->text());

        // A colour and its code
        $color = $form->filter('.crudit-ui-input-group--color:has(#client_color)');
        self::assertSame('2lenet--crudit-ui-bundle--color-code', $color->filter('#client_color')->attr('data-controller'));
        self::assertCount(1, $color->filter('[data-color-code]'));

        // Short choices with one answer: a segmented control in a fieldset
        $priority = $form->filter('#client_priority');
        self::assertSame('crudit-ui-segmented', $priority->attr('class'));
        self::assertSame(['None', 'Low', 'High'], $priority->filter('.crudit-ui-segmented__label')->each(fn (Crawler $label) => $label->text()));
        self::assertCount(3, $priority->filter('input.crudit-ui-segmented__input[type="radio"]'));

        // A date in lists: on one line
        self::assertStringContainsString('crudit-ui-date-lists', (string) $form->filter('#client_createdAt')->attr('class'));
    }

    public function testATelephoneInErrorIsAttachedToItsError(): void
    {
        $crawler = $this->client->request('GET', '/client/new');
        $form = $crawler->filter('main form')->form();
        $form->setValues(['client[name]' => 'Bob', 'client[phone][number]' => 'abc']);
        $crawler = $this->client->submit($form);

        self::assertResponseStatusCodeSame(200);
        foreach (['#client_phone_number', '#client_phone_country'] as $selector) {
            self::assertSame('client_phone_error', $crawler->filter($selector)->attr('aria-describedby'), $selector);
            self::assertSame('true', $crawler->filter($selector)->attr('aria-invalid'), $selector);
        }
        self::assertCount(1, $crawler->filter('#client_phone_error'));
    }

    public function testAnErrorIsAttachedToItsField(): void
    {
        $crawler = $this->client->request('GET', '/client/new');
        $form = $crawler->filter('main form')->form();
        $form->setValues(['client[name]' => '']);
        $crawler = $this->client->submit($form);

        self::assertResponseStatusCodeSame(200);
        $input = $crawler->filter('#client_name');
        self::assertSame('true', $input->attr('aria-invalid'));
        self::assertSame('client_name_help client_name_error', $input->attr('aria-describedby'));
        self::assertSame('Enter the name of the client.', trim($crawler->filter('#client_name_error')->text()));
        self::assertCount(1, $crawler->filter('#client_name_error svg'));
        self::assertStringContainsString('crudit-ui-field--invalid', (string) $input->closest('.crudit-ui-field')?->attr('class'));
    }

    private function form(): Crawler
    {
        $crawler = $this->client->request('GET', '/client/new');
        self::assertResponseIsSuccessful();

        return $crawler->filter('main form.crudit-form__form');
    }
}
