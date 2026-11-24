<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use Lle\CruditBundle\Form\Type\AutocompleteType;
use Lle\CruditBundle\Form\Type\TelephoneType;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Twig\Environment;

/**
 * The form of a CRUD (lot 3.2.a), with bootstrap_5_layout as the global theme of the application (as 24 projects):
 * the theme of Crudit is imposed. Labels above and attached, required fields explained, help and errors attached,
 * sections with their own columns, widths of the fields, a switch, choices shown as boxes, a unit. The form to edit a
 * client (ClientEditType): the widgets of other bundles, titles of sections under the title of the form, a translation
 * domain, the error of a sub-form laid out field by field. The base types (lot 3.2.b): keyboards of a phone, autofill
 * kept away from the inputs typed in only, counter of characters, format helpers, telephone, colour, segmented
 * control, date in lists, a boolean guessed as a switch. The rich types (lot 3.2.c): an entity and a choice with
 * several answers in a list with search, collections, the file of Vich.
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

        // A unit: next to the input, read with it (aria-describedby)
        $progress = $form->filter('#client_progress');
        $unit = $progress->closest('.crudit-ui-input-group')?->filter('.crudit-ui-input-group__unit');
        self::assertSame(['%', 'client_progress_unit', null], [$unit?->text(), $unit?->attr('id'), $unit?->attr('aria-hidden')]);
        self::assertContains('client_progress_unit', explode(' ', (string) $progress->attr('aria-describedby')));

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

        self::assertResponseStatusCodeSame(422);
        foreach (['#client_phone_number', '#client_phone_country'] as $selector) {
            self::assertSame('client_phone_error', $crawler->filter($selector)->attr('aria-describedby'), $selector);
            self::assertSame('true', $crawler->filter($selector)->attr('aria-invalid'), $selector);
        }
        self::assertCount(1, $crawler->filter('#client_phone_error'));
    }

    public function testATelephoneKeepsTheDescriptionsOfTheProject(): void
    {
        $view = $this->formFactory()->createNamedBuilder('client', FormType::class, null, ['csrf_protection' => false])
            ->add('phone', TelephoneType::class, ['help' => 'A mobile'])
            ->getForm()->createView();
        $view['phone']['number']->vars['attr']['aria-describedby'] = 'client_phone_note';
        $html = new Crawler($this->renderWidget($view, "{{ form_row(form.phone) }}", '@LleCrudit/form/theme.html.twig'));

        self::assertSame('client_phone_note client_phone_help', $html->filter('#client_phone_number')->attr('aria-describedby'), 'the description of the project is kept, the help added');
        self::assertSame('client_phone_help', $html->filter('#client_phone_country')->attr('aria-describedby'));
    }

    public function testABooleanGuessedIsASwitchInTheThemeOfCruditOnly(): void
    {
        // A name for each theme: the renderer of Symfony keeps the blocks found for a field by its name
        $view = fn (string $name): FormView => $this->formFactory()->createNamedBuilder($name, FormType::class, null, ['csrf_protection' => false])
            ->add('active', CheckboxType::class, ['switch' => true])
            ->getForm()->createView();

        $crudit = new Crawler($this->renderWidget($view('crudit'), '{{ form_row(form.active) }}', '@LleCrudit/form/theme.html.twig'));
        self::assertSame('switch', $crudit->filter('#crudit_active')->attr('role'));
        self::assertCount(1, $crudit->filter('label.crudit-ui-switch:has(#crudit_active)'));

        $bootstrap = new Crawler($this->renderWidget($view('bootstrap'), '{{ form_row(form.active) }}', 'bootstrap_5_layout.html.twig'));
        self::assertSame('checkbox', $bootstrap->filter('#bootstrap_active')->attr('type'));
        self::assertNull($bootstrap->filter('#bootstrap_active')->attr('role'), 'another theme draws a checkbox: it is told as a checkbox');
    }

    public function testAnErrorIsAttachedToItsField(): void
    {
        $crawler = $this->client->request('GET', '/client/new');
        $form = $crawler->filter('main form')->form();
        $form->setValues(['client[name]' => '']);
        $crawler = $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        $input = $crawler->filter('#client_name');
        self::assertSame('true', $input->attr('aria-invalid'));
        self::assertSame('client_name_help client_name_error', $input->attr('aria-describedby'));
        self::assertSame('Enter the name of the client.', trim($crawler->filter('#client_name_error')->text()));
        self::assertCount(1, $crawler->filter('#client_name_error svg'));
        self::assertStringContainsString('crudit-ui-field--invalid', (string) $input->closest('.crudit-ui-field')?->attr('class'));
    }

    public function testTheWidgetsOfOtherBundles(): void
    {
        $form = $this->editForm();

        // the global themes of the project (FOSCKEditorBundle, VichUploaderBundle) kept before the one of Crudit: the editor, not a bare textarea; the file of Vich rendered by Crudit
        $editor = $form->filter('textarea#client_presentation');
        self::assertCount(1, $editor);
        self::assertStringContainsString('CKEDITOR.replace("client_presentation"', implode("\n", $form->filter('script')->each(fn (Crawler $script) => $script->text())));
        self::assertCount(1, $form->filter('.crudit-ui-file input[type="file"]#client_attachmentFile_file'), 'the file of Vich: the theme of Crudit comes last');
        // Still no Bootstrap (the global theme of the application)
        self::assertCount(0, $form->filter('.form-control, .form-select, .mb-3'));
    }

    public function testTheTitlesOfTheSectionsUnderTheTitleOfTheForm(): void
    {
        $form = $this->editForm()->closest('.crudit-form');

        self::assertSame('Details', $form?->filter('h2.crudit-form__title')->text());
        self::assertSame(['Identity', 'Contact', 'Details', 'More about the client'], $form?->filter('fieldset.crudit-form-section > legend h3')->each(fn (Crawler $title) => $title->text()));
        self::assertCount(0, $form?->filter('fieldset.crudit-form-section > legend h2') ?? []);
        // Without a title of the form (the new client): titles of level 2
        self::assertCount(3, $this->form()->filter('fieldset.crudit-form-section > legend h2'));
    }

    public function testTheSectionAndTheTooltipInTheTranslationDomainOfTheField(): void
    {
        $form = $this->editForm();

        $section = $form->filter('fieldset.crudit-form-section')->last();
        self::assertSame('More about the client', $section->filter('legend h3')->text());
        self::assertSame('What the client does', $section->filter('.crudit-form-section__description')->text());
        self::assertSame('Presentation', trim($form->filter('label[for="client_presentation"]')->text()));
        self::assertSame('A few lines shown to the team', trim($form->filter('#client_presentation_tooltip')->text()));
    }

    public function testTheErrorOfASubFormLaidOutFieldByField(): void
    {
        $crawler = $this->client->request('GET', '/client/edit/' . $this->idOf('Alice'));
        $form = $crawler->filter('main form')->form();
        $form->setValues(['client[address][city]' => 'Lyon', 'client[address][postcode]' => '']);
        $crawler = $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        // On a line of its own, before the fields of the sub-form
        $line = $crawler->filter('.crudit-form-grid > .crudit-form-field--span-full')->reduce(fn (Crawler $line) => str_contains($line->text(), 'Enter the postcode of the city.'));
        self::assertCount(1, $line);
        self::assertSame(1, $line->nextAll()->filter('.crudit-ui-field')->reduce(fn (Crawler $field) => $field->filter('#client_address_city')->count() > 0)->count());
    }

    public function testAnEntityIsAListWithSearchAskedToTheServer(): void
    {
        $form = $this->form();
        $root = $form->filter('.crudit-ui-combobox')->reduce(fn (Crawler $box) => $box->filter('#client_referrer')->count() > 0);

        self::assertSame('2lenet--crudit-ui-bundle--combobox', $root->attr('data-controller'));
        self::assertSame('/client/autocomplete', $root->attr('data-2lenet--crudit-ui-bundle--combobox-url-value'));
        self::assertSame('false', $root->attr('data-2lenet--crudit-ui-bundle--combobox-multiple-value'));
        $input = $root->filter('#client_referrer');
        self::assertSame('combobox', $input->attr('role'));
        self::assertSame('off', $input->attr('autocomplete'), 'the list is the one of Crudit, not the autofill of the browser');
        self::assertSame('client_referrer_help', $input->attr('aria-describedby'));
        self::assertNull($input->attr('name'));
        self::assertSame('', $root->filter('input[type="hidden"][name="client[referrer]"]')->attr('value'));
        self::assertCount(1, $form->filter('label[for="client_referrer"]'), 'the label points to the input');
        self::assertCount(0, $form->filter('.entity-select, .choice-tom-select'), 'no Tom Select');
    }

    public function testAChoiceWithSeveralAnswersIsAListWithSearchInThePage(): void
    {
        $root = $this->form()->filter('.crudit-ui-combobox')->reduce(fn (Crawler $box) => $box->filter('#client_zones')->count() > 0);

        self::assertNull($root->attr('data-2lenet--crudit-ui-bundle--combobox-url-value'));
        self::assertSame('true', $root->attr('data-2lenet--crudit-ui-bundle--combobox-multiple-value'));
        self::assertSame(['South'], $root->filter('.crudit-ui-combobox__token-label')->each(fn (Crawler $label) => $label->text()));
        $select = $root->filter('select[name="client[zones][]"]');
        // group_by and preferred_choices: the groups flattened, the preferred choice (cst) once, in the order of the choices
        self::assertSame(['n', 's', 'e', 'w', 'cst'], $select->filter('option')->each(fn (Crawler $option) => $option->attr('value')));
        self::assertCount(0, $select->filter('optgroup'));
        self::assertSame(['s'], $select->filter('option[selected]')->each(fn (Crawler $option) => $option->attr('value')));
    }

    public function testTheTextOfTheRouteAutocompleteIsReceivedAsTyped(): void
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist((new Client())->setName('Lab')->setEmail('c++lab@example.org'));
        $em->flush();

        // The browser encodes the + (%2B): not decoded again into a space, which would find every e-mail with a "c"
        $this->client->request('GET', '/client/autocomplete', ['q' => 'c++', 'offset' => 0, 'limit' => 20]);
        $answer = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame(1, $answer['total_count']);
        self::assertSame(['Lab'], array_column($answer['items'], 'text'));

        $this->client->request('GET', '/client/autocomplete', ['q' => 'chloé', 'offset' => 0, 'limit' => 20]);
        $answer = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame(1, $answer['total_count']);
        self::assertSame(['Chloé'], array_column($answer['items'], 'text'));
    }

    public function testTheChoicesOfAListWithSearchAreSaved(): void
    {
        $bob = $this->idOf('Bob');
        $crawler = $this->client->request('GET', '/client/new');
        $form = $crawler->filter('main form')->form();
        $form->setValues(['client[name]' => 'Dan', 'client[referrer]' => (string) $bob]);
        $form['client[zones]']->select(['n', 'cst']);
        $this->client->submit($form);
        self::assertResponseRedirects();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $dan = $em->getRepository(Client::class)->findOneBy(['name' => 'Dan']);
        self::assertSame($bob, $dan?->getReferrer()?->getId());
    }

    public function testTheChoiceMadeIsShownByItsLabel(): void
    {
        $view = $this->formFactory()->createNamedBuilder('client', FormType::class, null, ['csrf_protection' => false])
            ->add('referrer', AutocompleteType::class, ['class' => Client::class, 'route' => 'app_client_autocomplete', 'multiple' => true])
            ->getForm();
        $view->setData(['referrer' => [$this->entity('Chloé'), $this->entity('Alice')]]);
        $html = new Crawler($this->renderWidget($view->createView(), "{{ form_widget(form.referrer) }}", '@LleCrudit/form/theme.html.twig'));

        self::assertSame(['Chloé', 'Alice'], $html->filter('.crudit-ui-combobox__token-label')->each(fn (Crawler $label) => $label->text()), 'in the order of the value');
        self::assertSame($this->idOf('Chloé') . ',' . $this->idOf('Alice'), $html->filter('input[type="hidden"][name="client[referrer]"]')->attr('value'));
        self::assertSame('true', $html->filter('.crudit-ui-combobox')->attr('data-2lenet--crudit-ui-bundle--combobox-multiple-value'));
    }

    public function testTheValuesJoinedOfAListWithSearchFromTheServerAreItsEntities(): void
    {
        // The hidden input of the list with search sends the ids joined by a comma
        $form = $this->formFactory()->createNamedBuilder('client', FormType::class, null, ['csrf_protection' => false])
            ->add('referrer', AutocompleteType::class, ['class' => Client::class, 'route' => 'app_client_autocomplete', 'multiple' => true])
            ->getForm();
        $form->submit(['referrer' => $this->idOf('Chloé') . ',' . $this->idOf('Alice')]);

        self::assertTrue($form->isSynchronized());
        /** @var list<Client> $referrers */
        $referrers = $form->get('referrer')->getData();
        $names = array_map(fn (Client $client) => $client->getName(), $referrers);
        sort($names);
        self::assertSame(['Alice', 'Chloé'], $names);

        $form = $this->formFactory()->createNamedBuilder('client', FormType::class, null, ['csrf_protection' => false])
            ->add('referrer', AutocompleteType::class, ['class' => Client::class, 'route' => 'app_client_autocomplete', 'multiple' => true])
            ->getForm();
        $form->submit(['referrer' => $this->idOf('Alice') . ',999999']);
        self::assertFalse($form->get('referrer')->isSynchronized(), 'an unknown id is refused');
    }

    public function testTheRouteAutocompleteAnswersTheChoicesOfTheText(): void
    {
        $this->client->request('GET', '/client/autocomplete?q=bob&offset=0&limit=20');
        self::assertResponseIsSuccessful();
        /** @var array{items: list<array{id: int, text: string}>, next_offset: int, total_count: int} $data */
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);

        self::assertSame([['id' => $this->idOf('Bob'), 'text' => 'Bob']], $data['items']);
        self::assertSame(1, $data['total_count']);
        self::assertSame(20, $data['next_offset']);
    }

    public function testACollectionOfLinesInAnOrder(): void
    {
        $form = $this->editForm();

        $root = $form->filter('#client_contacts');
        self::assertSame('Contacts', trim($root->closest('fieldset')?->filter('legend')->text() ?? ''));
        self::assertSame('2lenet--crudit-ui-bundle--collection', $root->attr('data-controller'));
        self::assertSame('Contact %index%', $root->attr('data-2lenet--crudit-ui-bundle--collection-entry-value'), 'entry_label, translated in the domain of the field');
        self::assertSame('true', $root->attr('data-2lenet--crudit-ui-bundle--collection-sortable-value'));
        $lines = $root->filter('ol > li.crudit-ui-collection__row');
        self::assertSame(['Contact 1', 'Contact 2'], $lines->each(fn (Crawler $line) => $line->attr('aria-label')));
        self::assertSame('Name, Contact 2', trim($form->filter('label[for="client_contacts_1_name"]')->text()));
        self::assertSame('hidden', $form->filter('#client_contacts_1_position')->attr('type'));
        self::assertSame('2', $form->filter('#client_contacts_1_position')->attr('value'));
        self::assertSame(['Move up: Contact 2', 'Move down: Contact 2', 'Remove: Contact 2'], $lines->eq(1)->filter('.crudit-ui-collection__actions button .crudit-ui-visually-hidden')->each(fn (Crawler $text) => $text->text()));
        self::assertSame('Add: Contacts', $root->filter('.crudit-ui-collection__add')->attr('aria-label'));
        self::assertStringContainsString('client[contacts][__name__][name]', $root->filter('template')->html());

        // Without allow_add, allow_delete nor sortable: the lines only, a line of one field named by its line
        $languages = $form->filter('#client_languages');
        self::assertCount(0, $languages->filter('button, template'));
        self::assertSame('Line 2', $form->filter('label[for="client_languages_1"]')->text());
    }

    public function testTheErrorOfALineOfACollection(): void
    {
        $crawler = $this->client->request('GET', '/client/edit/' . $this->idOf('Alice'));
        $form = $crawler->filter('main form')->form();
        $form->setValues(['client[contacts][1][name]' => '']);
        $crawler = $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertSame('true', $crawler->filter('#client_contacts_1_name')->attr('aria-invalid'));
        self::assertCount(1, $crawler->filter('li[aria-label="Contact 2"] #client_contacts_1_name_error'));
    }

    public function testAnOrderOnAFieldThatIsNotHiddenIsRefused(): void
    {
        $this->expectException(\Symfony\Component\Form\Exception\InvalidConfigurationException::class);
        $this->formFactory()->create(FormType::class)
            ->add('contacts', \Symfony\Component\Form\Extension\Core\Type\CollectionType::class, ['entry_type' => \App\Form\ContactLineType::class, 'allow_add' => true, 'sortable' => 'name'])
            ->createView();
    }

    public function testTheFileAlreadySentOfVich(): void
    {
        $this->attachTo('Alice');
        $form = $this->editForm();

        $label = $form->filter('label[for="client_attachmentFile_file"]');
        self::assertSame('Attachment', trim($label->text()), 'the label points to the input');
        $input = $form->filter('.crudit-ui-file__zone > input[type="file"]#client_attachmentFile_file');
        self::assertSame('client[attachmentFile][file]', $input->attr('name'));
        self::assertSame('client_attachmentFile_help', $input->attr('aria-describedby'));
        $current = $form->filter('.crudit-ui-file__list > li');
        self::assertCount(1, $current);
        self::assertSame('front of the shop.svg', $current->filter('.crudit-ui-file__label')->text(), 'the original name kept by the mapping');
        self::assertSame('1 KB · current file', $current->filter('.crudit-ui-file__size')->text());
        // No preview for a VichFileType: its public uri may not be served (a private mapping, a download_route)
        self::assertCount(0, $current->filter('img'));
        self::assertSame('/uploads/clients/front-6745a1b2c3d4e.svg', $current->filter('a[download]')->attr('href'));
        self::assertSame('Delete front of the shop.svg', $current->filter('[data-2lenet--crudit-ui-bundle--file-drop-target="delete"] .crudit-ui-visually-hidden')->text());
        self::assertSame('1', $current->filter('input[type="checkbox"][hidden][name="client[attachmentFile][delete]"]')->attr('value'));
        self::assertCount(0, $form->filter('.vich-file, label[for="client_attachmentFile_delete"]'), 'the checkbox of Vich is not shown again');

        // Without file: no list, no checkbox
        $crawler = $this->client->request('GET', '/client/edit/' . $this->idOf('Bob'));
        self::assertCount(0, $crawler->filter('.crudit-ui-file__list > li, input[name="client[attachmentFile][delete]"]'));
    }

    public function testTheFileAskedToBeDeletedIsDeleted(): void
    {
        $this->attachTo('Alice');
        $crawler = $this->client->request('GET', '/client/edit/' . $this->idOf('Alice'));
        $form = $crawler->filter('main form')->form();
        // The button Delete checks the hidden checkbox
        $form['client[attachmentFile][delete]']->tick();
        $this->client->submit($form);

        self::assertResponseRedirects();
        self::assertNull($this->entity('Alice')->getAttachmentName());
    }

    private function attachTo(string $name): void
    {
        $front = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 4 3"><rect width="4" height="3"/></svg>';
        (new \Symfony\Component\Filesystem\Filesystem())->dumpFile(static::getContainer()->getParameter('kernel.project_dir') . '/public/uploads/clients/front-6745a1b2c3d4e.svg', $front);
        $client = $this->entity($name)
            ->setAttachmentName('front-6745a1b2c3d4e.svg')
            ->setAttachmentOriginalName('front of the shop.svg')
            ->setAttachmentMimeType('image/svg+xml')
            ->setAttachmentSize(\strlen($front));
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->flush();
        $em->clear();
        unset($client);
    }

    private function entity(string $name): Client
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $client = $em->getRepository(Client::class)->findOneBy(['name' => $name]);
        self::assertNotNull($client);

        return $client;
    }

    private function idOf(string $name): int
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $client = $em->getRepository(Client::class)->findOneBy(['name' => $name]);
        self::assertNotNull($client);

        return (int) $client->getId();
    }

    private function editForm(): Crawler
    {
        $crawler = $this->client->request('GET', '/client/edit/' . $this->idOf('Alice'));
        self::assertResponseIsSuccessful();

        return $crawler->filter('main form.crudit-form__form');
    }

    private function form(): Crawler
    {
        $crawler = $this->client->request('GET', '/client/new');
        self::assertResponseIsSuccessful();

        return $crawler->filter('main form.crudit-form__form');
    }

    private function formFactory(): FormFactoryInterface
    {
        /** @var FormFactoryInterface $factory */
        $factory = static::getContainer()->get('form.factory');

        return $factory;
    }

    private function renderWidget(FormView $view, string $template, string $theme): string
    {
        /** @var Environment $twig */
        $twig = static::getContainer()->get('twig');

        return $twig->createTemplate("{% form_theme form with ['" . $theme . "'] only %}" . $template)->render(['form' => $view]);
    }
}
