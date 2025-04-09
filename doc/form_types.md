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

### Markdown

The [Lle\CruditBundle\Form\Type\MarkdownType](markdown.md#markdowntype) allows you to create a Markdown editor textarea.

### Cron expression

The `Lle\CruditBundle\Form\Type\CronExpressionType` allows you to create a cron input with a interface to select value.

### CollectionType

Crudit works with CollectionType, but you need to do configure some things on your side.

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
