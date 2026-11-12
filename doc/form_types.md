## Custom CrudIT form types

### Autocomplete

The Lle\CruditBundle\Form\Type\AutocompleteType
allows you to create a select for entities that is
fed by an AJAX autocomplete.
It is equivalent to Doctrine's EntityType.

Options :

| Name     | Default  | Description                                     |
|----------|----------|-------------------------------------------------|
| class    | required | a FQCN of your entity (e.g. App\Entity\Dummy)   |
| multiple | false    | set this to true if the field is a collection   |
| route    | null     | if you want to customize the autocomplete route |
| url      | null     | if you want to customize the autocomplete url   |

### FileType

The `Lle\CruditBundle\Form\Type\FileType` allows you to create a file input with an interface to show or delete the current file.

Here's a working example:
```php
$builder->add('documentFile', FileType::class, [
    'allow_delete' => true,
    'download_route' => 'app_crudit_entity_download', // Your own route to download the file
    'image_route' => 'app_crudit_entity_downloadthumbnail', // Your own route to get the file (or the thumbnail)
]);
```

### Markdown

The [Lle\CruditBundle\Form\Type\MarkdownType](markdown.md#markdowntype) allows you to create a Markdown editor textarea.

### Cron expression

The `Lle\CruditBundle\Form\Type\CronExpressionType` allows you to create a cron input with a interface to select value.

### Telephone

The `Lle\CruditBundle\Form\Type\TelephoneType` renders a calling code select (`Switzerland (+41)`) next to a number input,
and stores the number as an E.164 string (`+41791234567`) in a plain string property. No external library is needed.
Both are on one line, the "+33" box first (on the right in a right-to-left language): the native select of the
countries lies transparent over the box, which shows the calling code chosen (controller `telephone`). One focus ring
surrounds the box and the number; both are attached to the help and the error of the field.

```php
$builder->add('telephone', TelephoneType::class, [
    'default_country' => 'CH',
    'preferred_countries' => ['CH', 'FR', 'IT'],
]);
```

Options :

| Name                | Default | Description                                                    |
|---------------------|---------|----------------------------------------------------------------|
| default_country     | FR      | ISO code selected when the field is empty                      |
| preferred_countries | []      | ISO codes displayed at the top of the select (unknown codes are rejected) |

Validation happens on submit: only digits (spaces, dots, dashes, slashes and parentheses allowed), and the number of
digits after the calling code is checked for the main European countries and North America (generic E.164 bounds
elsewhere). A leading trunk `0` is removed (`06 12 34 56 78` → `+33612345678`), except for Italy. A number typed with
`+` or `00` takes precedence over the selected country.

#### Existing (legacy) values

Switching an existing string field to `TelephoneType` breaks nothing: a stored value that is not E.164 (free text such
as `06 12 34 56 78`, or an unknown calling code) is never guessed from `default_country`:

- it is displayed as is in the number input, with no calling code selected (`+ ?` in the box, an option offered
  in the select for such values only);
- submitted untouched, it is saved back unchanged, so the rest of the form can be saved;
- once the user picks a calling code (or types the number with `+`/`00`), it is validated and converted to E.164.
  Changing the number without choosing a calling code raises `crudit.telephone.calling_code_required`.

Legacy values thus stay as they are until someone edits them. If you need E.164 everywhere (SMS, CRM sync…), migrate
the existing data when switching the field: `TelephoneTransformer::isLegacy()` spots the values to convert, and
`reverseTransform()` on a new transformer instance converts them with the country known for each record.

The widget is the `crudit_telephone_widget` block (`.crudit-telephone` input group): a compact box showing the calling
code (`+41`, from the `calling_code` view variable) on the left, with the native country select laid transparent over
it (no TomSelect), and the number input on the right. Each country option carries its calling code in a `data-code`
attribute, copied into the box on change. Override the block in your own form theme to restyle it. With `form_row()`,
the `crudit_telephone_row` block renders a regular `<label>` pointing to the number input, instead of the
`<fieldset>`/`<legend>` Bootstrap uses for compound fields.

Display stored numbers with the `telephone` Twig filter (`+41 79 123 45 67`). Formats exist for a set of calling codes
(`CruditTelephoneFilterExtension::FORMATS`: `+1`, `+32`, `+33`, `+34`, `+41`, `+49`, French overseas, Maghreb…); other
numbers, Italian ones (`+39`) for instance, are displayed unformatted.

### CollectionType

Crudit works with CollectionType, but you need to do configure some things on your side.

In the forms of Crudit, nothing to configure: the bricks impose the theme of Crudit (`@LleCrudit/form/theme.html.twig`).
Here's a working example:
```php
// in parent form OrderType.php
$builder->add('lines', CollectionType::class, [
    'label' => 'field.lines',
    'entry_type' => LineType::class,
    'allow_add' => true,
    'allow_delete' => true,
    'by_reference' => false,
    'prototype_name' => '__etape_name__', // not required unless you use multiple CollectionType
]);

// in subform LineType.php
$builder->add('ordre', ItemType::class, [
    'label' => 'field.item',
    'row_attr' => [
        'class' => 'col-1',
    ]
]);
```

What matters :
* by_reference is **NECESSARY** because otherwise Doctrine won't be able to track changes in collection, resulting in deletions that don't happen.
* prototype_name is necessary when you nest CollectionTypes (a CollectionType inside another CollectionType)
* use row_attr with Bootstrap columns to size the fields, to get a satisfying result

## Layout of a form

The forms of the bricks impose the theme of Crudit, whatever `twig.form_themes`: labels above the inputs, the help and
the error attached to them, the required fields explained above the form. The fields are on a grid of at most
`lle_crudit.form_columns` per line (4 by default), fewer when the room is short, one on a phone.

```php
$builder
    // A section: the fields after it, up to the next one, in a fieldset with a title
    ->add('identity', GroupType::class, ['label' => 'Identity', 'mapped' => false])
    ->add('name', null, ['help' => 'As on the invoice', 'tooltip' => 'The name of the company'])
    ->add('email', null, ['span' => 2])                       // 2 columns
    // A section of 2 columns, with a text under its title
    ->add('contact', GroupType::class, ['label' => 'Contact', 'description' => 'How to reach the client', 'columns' => 2, 'mapped' => false])
    ->add('active', CheckboxType::class, ['required' => false, 'switch' => true]) // a switch (a guessed boolean is one)
    ->add('size', ChoiceType::class, ['choices' => ['S' => 's', 'M' => 'm', 'L' => 'l'], 'expanded' => true, 'segmented' => true])
    ->add('notes', TextareaType::class, ['required' => false, 'span' => 'full', 'attr' => ['maxlength' => 500]]); // the whole line, a counter
```

| Option | On | |
|---|---|---|
| `span` | any field | `1` (default), `2`, `3` or `'full'`: its width on the grid; the whole line on a phone, and when it is wider than its section (or `3` on a narrow screen). `attr.class` does not set the width |
| `columns` | `GroupType` | the most fields on a line of this section (1 to 4), else those of the form |
| `description` | `GroupType` | a text under the title of the section |
| `tooltip`, `tooltip_html`, `tooltip_template` | any field | a help opened by a button next to the label (`tooltip_html` is cleaned by `sanitize`) |
| `switch` | `CheckboxType` | a switch, its state written Yes / No (`true` on a boolean guessed from the entity; `false` keeps the checkbox) |
| `segmented` | `ChoiceType` | with `expanded` and without `multiple`: short choices as a segmented control |
| `attr.maxlength` | long text | a counter of characters under the field ("27 / 80 characters") |
| `counter` | text, long text | `true`: the counter on a text on one line too (Symfony puts the length of the column in `maxlength`, so it is not automatic); `false`: no counter |
| `attr.class` `crudit-upper-format`, `crudit-lower-format`, `crudit-email-format`, `crudit-ip-format` | text | the value in capitals, in small letters, an e-mail or an IPv4 address checked, once the field is left (controller `input-format`); capitals and small letters shown while typing |

The keyboard of a phone follows the kind of the value (`inputmode`), and a number is typed in Latin digits whatever
the language (in Arabic or Persian digits, it is refused).
The controllers `char-count`, `color-code` (CruditUiBundle) and `input-format` (Crudit) are to be enabled
in `assets/controllers.json`.

The number of columns of a form: `FormConfig::setColumns()`, else `getFormColumns()` of its CRUD, else
`lle_crudit.form_columns`.
