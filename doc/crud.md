To create a crud, simply type this command in the console of your project:

```php bin/console make:crudit```

You will then be asked a series of questions :

- the name of your Entity
- the namespace of your Controller
- if the command should also create filters related to your entity's fields

That's it! Several files have been created:

- ```src/Crudit/Config/EntityCrudConfig.php```
- ```src/Controller/Crudit/EntityController.php```
- ```src/Form/EntityType.php```
- ```src/Crudit/Datasource/Filterset/EntityFilterSet.php```
- ```src/Crudit/Datasource/EntityDatasource.php```

## How to sort your list:

```php
  public function getDefaultSort(): array
    {
        return [['numeroLot', 'ASC']];
    }
```

## How to configure the choices of the number of items to display:

```php
  public function getChoicesNbItems(): array
  {
      return [15, 30, 45, 60];
  }
```

## How to add actions:

It is possible to add actions to lists, list items and shows.
It is already possible to add action in a dropdown, adding `->setDropdown(true)` to the action object.

```php
  public function getListActions(): array
  {
      $res[] = ListAction::new(
          "action.export",
          $this->getPath(CrudConfigInterface::EXPORT),
          Icon::new("file-export")
      )
          ->setModal("@LleCrudit/modal/_export.html.twig")
          ->setConfig(
              [
                  "export" => [Exporter::CSV, Exporter::EXCEL],
              ]
          );
          
      $res[] = ListAction::new(
          'action.import.csv',
          Path::new('import_lot_melange'),
          Icon::new('download')
      )->setDropdown(true);

      $res[] = ListAction::new(
          'action.batch.bons_livraisons',
          Path::new('create_bons_livraisons'),
          Icon::new('truck-loading')
      )->setIsBatch();

      return $res;
  }
```

If you want to add a role for your action:

```php
  public function getListActions(): array
  {          
      $res[] = ListAction::new(
          'action.import.csv',
          (Path::new('import_lot_melange'))->setRole('ROLE_IMPORT'),
          Icon::new('download')
      )->setDropdown(true);

      return $res;
  }
```

## Write actions that will be executed in your crud

When we declare a new action in our page, we indicate the path of our method. This method must be in
```src/Controller/Crudit/EntityController.php```

## Configure the form to be displayed in the new and edit screens

In ```src/Form/EntityType.php```. The form is a basic Symfony form. Several formtypes are available.
See : [Form types](form_types.md)

![](img/form.png)

## Configure the filters in your list

In  ```src/Crudit/Datasource/Filterset/EntityFilterSet.php```. See [Filters](filter.md)

![](img/filter.png)

## Enable striped tables

To enable striped tables in the lists, you must add this scss :

```scss
.crudit-list {
    & > tbody > tr:nth-of-type(odd) {
        --#{$variable-prefix}table-accent-bg: var(--#{$variable-prefix}table-striped-bg);
        color: var(--#{$variable-prefix}table-striped-color);
    }
}
```

> :warning: **Don't forget to add your new crud to the menu in src/Crudit/CrudMenu/AppMenuProvider.php:**

```php
LinkElement::new(
    'menu.groupes',
    Path::new('app_crudit_contact_index'),
    Icon::new('/img/icons/contact.svg', Icon::TYPE_IMG),
    "ROLE_CONTACT_INDEX"
)
```

## How to add totals on your list:

It is possible to add totals to your list by adding the `getTotalsFields` method to the CrudConfig file.

```php
public function getTotalFields(): array
{
    return [
        'montantHt' => [
            'type' => CrudConfigInterface::SUM,
            'field' => Field::new('montantHt', 'currency'),
        ],
        'montantTtc' => [
            'type' => CrudConfigInterface::SUM,
            'field' => Field::new('montantTtc', 'currency'),
        ],
    ];
}
```

You can choose between 3 types of totals, `AVERAGE`, `SUM` and `COUNT`.
To use them, use the constants defined in the `CrudConfigInterface` file.

> :warning: **Don't forget to specify the type of your field, as Crudit is unable to determine this itself.**

## Configure route for DoctrineEntityField

To configure route for DoctrineEntityField, you must set the `route` options:

```php
Field::new('yourdoctrinentityfield', null, ['route' => 'your_route']);
```

Or:

```php
Field::new('yourdoctrinentityfield')->setOptions(['route' => 'your_route']);
```

If you want to add a role for your DoctrineEntityField:

```php
Field::new('yourdoctrinentityfield', null, ['route' => 'your_route', 'routeRole' => 'YOUR_ROLE']);
```

Or:

```php
Field::new('yourdoctrinentityfield')->setOptions(['route' => 'your_route', 'routeRole' => 'YOUR_ROLE']);
```
