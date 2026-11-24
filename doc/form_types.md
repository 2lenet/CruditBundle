## Custom CrudIT form types

### Autocomplete

The `Lle\CruditBundle\Form\Type\AutocompleteType` lets the user choose an entity in a list with search, whose
choices are asked to the server. It is equivalent to Doctrine's `EntityType`, and is guessed for an association.

Options :

| Name     | Default  | Description                                                                          |
|----------|----------|--------------------------------------------------------------------------------------|
| class    | required | a FQCN of your entity (e.g. App\Entity\Dummy)                                         |
| multiple | false    | set this to true if the field is a collection                                        |
| route    | null     | the route of the choices (default `app_crudit_<entity>_autocomplete`, the CRUD's own) |
| url      | null     | the address of the choices, instead of a route                                       |

The list (lot 3.2.c) is the list with search of CruditUiBundle (`docs/forms.md` of CruditUiBundle): the choices made
are tokens in the field, each with a button that removes it; the arrows move in the list, Enter chooses, Escape closes
it. The choices are asked to `GET <url>?q=<text>&offset=<n>&limit=20`: the first ones when the list opens, those of the
text typed after a pause, the next ones when the list is scrolled to its end. The route `autocomplete` of a CRUD answers
so (`{"items": [{"id", "text"}], "next_offset", "total_count"}`, the `searchFields` of the datasource, sorted by
`getDefaultAutocompleteSort()`). `next_offset` is the `offset` of the next page (`offset + limit`, even past the end):
the list asks no more once `next_offset` reaches `total_count`, a route of your own must give the same answer. The
text `q` is received as typed (no `urldecode()` needed). The value sent is the id, or the ids joined by a comma.
An entity without `__toString()` is shown with its id.

Enable the controller `combobox` of `@2lenet/crudit-ui-bundle` in `assets/controllers.json`
(`{"enabled": true, "fetch": "lazy"}`). Without it the native `<select>` stays hidden and the choices cannot be
changed: this goes for every list with search of the theme of Crudit, a `ChoiceType` with several answers too.

### Choices: native list or list with search

A `ChoiceType` (and an `EntityType`) shown as a list (not `expanded`) is:

- with **several answers** (`multiple`): a list with search, its choices filtered in the page;
- with **one answer**: the native `<select>`.

The option `combobox` changes it: `true` for a long list with one answer, `false` for a native list with several
answers. It needs a list (`expanded: false`).

```php
$builder->add('country', CountryType::class, ['combobox' => true]);
```

### FileType

A file of VichUploaderBundle (`VichFileType`, `VichImageType`) or the `Lle\CruditBundle\Form\Type\FileType` is the file
of CruditUiBundle: a drop zone covered by the true input (a click opens the picker, a file dropped on it is chosen), the
file chosen listed with its size and a preview for an image. The file already sent shows its name, its size, a preview
for an image, a link "Download" and, with `allow_delete`, a button "Delete *name*" (it checks the checkbox `delete` of
Vich, hidden; "Cancel the deletion" puts it back). Its name is the original name and its size the one kept by the
mapping, when it keeps them:

```php
#[Vich\UploadableField(mapping: 'documents', fileNameProperty: 'documentName', size: 'documentSize', originalName: 'documentOriginalName', mimeType: 'documentMimeType')]
private ?File $documentFile = null;
```

The `FileType` of Crudit adds its own routes:
```php
$builder->add('documentFile', FileType::class, [
    'allow_delete' => true,
    'download_route' => 'app_crudit_entity_download', // Your own route to download the file
    'image_route' => 'app_crudit_entity_downloadthumbnail', // Your own route to get the file (or the thumbnail)
]);
```

One file per field. Enable the controller `file-drop` of CruditUiBundle (`assets/controllers.json`).

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
it, and the number input on the right. Each country option carries its calling code in a `data-code`
attribute, copied into the box on change. Override the block in your own form theme to restyle it. With `form_row()`,
the `crudit_telephone_row` block renders a regular `<label>` pointing to the number input, instead of the
`<fieldset>`/`<legend>` Bootstrap uses for compound fields.

Display stored numbers with the `telephone` Twig filter (`+41 79 123 45 67`). Formats exist for a set of calling codes
(`CruditTelephoneFilterExtension::FORMATS`: `+1`, `+32`, `+33`, `+34`, `+41`, `+49`, French overseas, Maghreb…); other
numbers, Italian ones (`+39`) for instance, are displayed unformatted.

### CollectionType

In the forms of Crudit, nothing to configure: the bricks impose the theme of Crudit (`@LleCrudit/form/theme.html.twig`),
and a collection is the one of CruditUiBundle: a list of lines named "Line 1", "Line 2"…, each field saying its line to
screen readers, the buttons "Add" (`allow_add`) and "Remove" (`allow_delete`). On a wide screen the fields of a line are
side by side, their labels on the first line only. Enable the controller `collection` of CruditUiBundle
(`assets/controllers.json`).

Here's a working example:
```php
// in parent form OrderType.php
$builder->add('lines', CollectionType::class, [
    'label' => 'field.lines',
    'entry_type' => LineType::class,
    'allow_add' => true,
    'allow_delete' => true,
    'by_reference' => false,
    'prototype_name' => '__line_name__', // not required unless you nest CollectionTypes
    'entry_label' => 'field.line', // "Line %index%" by default: "Piece %index%", in the domain of the field
    'sortable' => 'position', // buttons Move up and Move down; the field of the position of a line
]);

// in subform LineType.php
$builder
    ->add('item', ItemType::class, ['label' => 'field.item'])
    ->add('quantity', IntegerType::class, ['label' => 'field.quantity'])
    ->add('position', HiddenType::class);

// in the entity Order
#[ORM\OneToMany(targetEntity: Line::class, mappedBy: 'order', cascade: ['persist'], orphanRemoval: true)]
#[ORM\OrderBy(['position' => 'ASC'])]
private Collection $lines;
```

What matters :
* by_reference is **NECESSARY** because otherwise Doctrine won't be able to track changes in collection, resulting in deletions that don't happen.
* prototype_name is necessary when you nest CollectionTypes (a CollectionType inside another CollectionType)
* `sortable` names a `HiddenType` of the entry type (else an `InvalidConfigurationException`): 1, 2, 3… are written in
  it each time the lines change. The order is kept only if the entity reads its lines in this order (`#[ORM\OrderBy]`).

## Layout of a form

The forms of the bricks impose the theme of Crudit, whatever the layout of `twig.form_themes`: labels above the inputs,
the help and the error attached to them, the required fields explained above the form. The other global themes of the
project (the widgets of FOSCKEditor and Vich, the blocks of its own types) are kept, after the theme of Crudit only the
layouts of Symfony are left out. The fields are on a grid of at most
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
| `span` | any field | `1` (default), `2`, `3` or `'full'`: its width on the grid; the whole line on a phone, when it is wider than its section, and when the grid has no room for that many columns (a section, a dialog, a narrow column). `attr.class` does not set the width. Refused on a `GroupType` and on a sub-form laid out field by field |
| `columns` | `GroupType` | the most fields on a line of this section (1 to 4), else those of the form |
| `description` | `GroupType` | a text under the title of the section |
| `tooltip`, `tooltip_html`, `tooltip_template` | any field | a help opened by a button next to the label (`tooltip_html` is cleaned by `sanitize`) |
| `switch` | `CheckboxType` | a switch, its state written Yes / No (`true` on a boolean guessed from the entity; `false` keeps the checkbox). `role="switch"` is set by the theme of Crudit only: another theme keeps a checkbox |
| `segmented` | `ChoiceType` | with `expanded` and without `multiple`: short choices as a segmented control |
| `attr.maxlength` | long text | a counter of characters under the field ("27 / 80 characters") |
| `counter` | text, long text | `true`: the counter on a text on one line too (Symfony puts the length of the column in `maxlength`, so it is not automatic); `false`: no counter |
| `attr.class` `crudit-upper-format`, `crudit-lower-format`, `crudit-email-format`, `crudit-ip-format` | text | the value in capitals, in small letters, an e-mail or an IPv4 address checked, once the field is left (controller `input-format`); capitals and small letters shown while typing |

The keyboard of a phone follows the kind of the value (`inputmode`), and a number is typed in Latin digits whatever
the language (in Arabic or Persian digits, it is refused).
The controllers `char-count`, `color-code` (CruditUiBundle) and `input-format` (Crudit) are to be enabled
in `assets/controllers.json`.

The title and the description of a section and the `tooltip` of a field are translated in the `translation_domain` of
the field, as the labels. The title of a section is a `<h2>`, a `<h3>` under the title of the form.

A sub-form (a compound field of the form type) is laid out field by field on the grid, its own errors (a constraint on
the object) on a line above its fields.

The number of columns of a form: `FormConfig::setColumns()`, else `getFormColumns()` of its CRUD (1 to 4, else a
`CruditException`), else `lle_crudit.form_columns`.
