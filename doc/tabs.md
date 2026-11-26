# Tabs

You can add tabs in the show screen, under the sections of the record: the component `Tabs` of CruditUiBundle (ARIA
pattern: arrows, Home, End; the tabs wrap on a phone). Enable its controller `tabs` in your `assets/controllers.json`.

In your CrudConfig:

```php
public function getTabs(): array
{
    return [
        'tab.name' => [Brick::new()]
    ];
}
```

You may use any brick, several in a tab (an array). Below are common use cases.

## The tab in the address

The tab chosen is in the address, `?tab=<id>`: read by the server, kept up to date when a tab is chosen (without a new
entry in the history), so a reload or a shared link opens the same tab. Link to a tab with the parameter:
`path('app_client_show', {id: client.id, tab: 'history'})`. A tab unknown or refused opens the first one.

The id of a tab is its label without `tab.`, in lower case ASCII, any other character than a letter, a digit, `_` or
`-` replaced by `_` (`tab.history` gives `history`, `tab.Pièces jointes` gives `pieces_jointes`), `_2` after an id
already taken. Give your own with `Tab::setId()` (or the argument `id` of `TabConfig::add()` / `adds()`).

## A counter

A counter after the label ("Parts 3"), computed on the record: give a `Tab` as the value, with `setCount()`. Its
label (not the key of the array) gives its id and its roles.

The counter is computed at each display of the record, for each tab shown: count without loading a collection. Map
the association with `fetch: 'EXTRA_LAZY'` (`->count()` is then a `COUNT` query), or count in a repository.

```php
use Lle\CruditBundle\Brick\TabBrick\Tab;

public function getTabs(): array
{
    return [
        'tab.parts' => Tab::new('tab.parts', [SublistConfig::new('intervention', $this->partCrudConfig)])
            // #[ORM\OneToMany(…, fetch: 'EXTRA_LAZY')] on parts: a COUNT, the parts are not loaded
            ->setCount(fn (Intervention $intervention): int => $intervention->getParts()->count()),
    ];
}
```

## A free content

A `TemplateConfig` renders a template of the project in the tab; it receives `resource` (the `ResourceView`,
`resource.resource` is the entity):

```php
'tab.report' => TemplateConfig::new(['template' => 'intervention/_report.html.twig']),
```

## A form in a tab

A `FormConfig` in a tab: once saved, when its redirect goes to the record (`setSuccessRedirectPath($this->getPath(
CrudConfigInterface::SHOW))`, or `getAfterEditPath()`), it comes back to its tab (`?tab=<id>` is added, unless the path
gives a `tab`).

```php
'tab.notes' => FormConfig::new()
    ->setForm(ClientNotesType::class)
    ->setSuccessRedirectPath($this->getPath(CrudConfigInterface::SHOW)),
```

## History

When using Gedmo\Loggable, you can use HistoryConfig in tabs. It provides a view of your entity's modifications.

If using the Loggable interface, this tab is automatically added.

```php
use Gedmo\Loggable\Loggable;

public class User implements Loggable
```

Note : if Gedmo ends up using PHP annotations, we may change the HistoryConfig to remove this requirement.

Otherwise, you have to add them like this:

```php
use Lle\CruditBundle\Brick\HistoryBrick\HistoryConfig;

// ...

public function getTabs(): array
{
    return [
        'tab.history' => [
            HistoryConfig::new()
        ]
    ];
}
```

If you need to add custom history (such as additions/deletions in collections), you have to log it yourself by
creating `LogEntry` entities from Gedmo, for example by implementing your own listener.

If you have some 1-1 relation for an entity you can add some entities to the History tabs

Just add a array with the datasource and the getter method to retrieve the entity from the main one.

exemple

```php
'tab.history' => [
    HistoryConfig::new(['otherEntities' => [
        ['datasource' => $this->depistageL1Datasource, 'method' => 'getDepistageL1'],
        ['datasource' => $this->depistageL2Datasource, 'method' => 'getDepistageL2'],
        ['datasource' => $this->bdiDatasource, 'method' => 'getBdi'],
        ['datasource' => $this->l2IADatasource, 'method' => 'getL2IA']
    ]]),
]
```

## Roles

A tab refused to the user is not shown, and its bricks are not built (its form is not bound, its counter not computed).
If you want to limit your tab to a role you can pass a third parameter to the addTabs function like this (or
`Tab::setRole()`; with `lle_crudit.generate_default_role`, `ROLE_<CRUD>_TAB_<LABEL>`)

```php
$tabConf = TabConfig::new();
$tabConf->adds('tab.subResource', [
    SublistConfig::new('resource', $this->subResourceCrudConfig)
        ->setFields($this->subResourceCrudConfig->getFields(CrudConfigInterface::INDEX))
], 'ROLE_TEST');
```

## How to display the tab under certain conditions

You can add conditions to the tab, using the resource or not, in order to decide whether to display the tab based on the data or the request.

To do this, simply use `setDisplayIf` (for the data) or `setDisplayIfByRequest` (for the request):

```php
$tabConf = TabConfig::new();
$tabConf->adds('tab.subResource', [
    SublistConfig::new('subResourceField', $this->subResourceCrudConfig)
        ->setFields($this->subResourceCrudConfig->getFields(CrudConfigInterface::INDEX))
        ->setActions($this->subResourceCrudConfig->getItemActions()),
    FormConfig::new()
        ->setForm(SubResourceSublistType::class)
        ->setSublist('subResourceField')
        ->setDatasource($this->subResourceCrudConfig->getDatasource())
        ->setSuccessRedirectPath($this->getPath(CrudConfigInterface::SHOW)),
], 'ROLE_RESOURCE_TAB_SUBRESOURCE', fn(Resource $resource) => $resource->isActive(), fn(Request $request) => $request->query->has('actif'));
```

## Sublists

See [Sublists](sublist.md)

## Tags

See [Tags](brick/tags.md)
