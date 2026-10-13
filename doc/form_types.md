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
| preferred_countries | []      | ISO codes displayed at the top of the select                   |

Validation happens on submit: only digits (spaces, dots, dashes and parentheses allowed), and the number of digits after
the calling code is checked for the main European countries and North America (generic E.164 bounds elsewhere).
A leading trunk `0` is removed (`06 12 34 56 78` → `+33612345678`), except for Italy. A number typed with `+` or `00`
takes precedence over the selected country.

Existing values that are not E.164 (legacy free text) are displayed as is in the number input, and converted on next save.

The widget is the `crudit_telephone_widget` block (`.crudit-telephone` input group): override it in your own form theme
to restyle it. Display stored numbers with the `telephone` Twig filter (`+41 79 123 45 67`).

### CollectionType

Crudit works with CollectionType, but you need to do configure some things on your side.

First, you need to include @LleCrudit/form/custom_types.html.twig in Twig templates:
```yaml
twig:
    form_themes:
        - 'bootstrap_5_layout.html.twig'
        - '@LleCrudit/form/custom_types.html.twig'
```

Then you can use the CollectionType. Here's a working example:
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
