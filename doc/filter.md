# Filters

How to make filters on lists

**1. In the DataSource.php file linked to your entity**

```php
/**
 * @required
 */
public function setFilterset(CollecteFilterset $filterset)
{
    $this->filterset = $filterset;
}
```

> :warning: **The PHPDoc `@required` is mandatory, otherwise the method will be ignored.**

**2. Then, create the file CollecteFilterset.php that you declared as a parameter above**

In the getFilters() method, you can declare the desired filters

      <?php

      namespace App\Crudit\Datasource\Filterset;

      use Lle\CruditBundle\Datasource\AbstractFilterSet;
      use Lle\CruditBundle\Filter\FilterType\StringFilterType;
      use Lle\CruditBundle\Filter\FilterType\NumberFilterType;
      
      class CollecteFilterset extends AbstractFilterSet
      {
          public function getFilters(): array
          {
              return [
                  StringFilterType::new('numeroFiche'),
                  NumberFilterType::new('poidsFruit')
              ];
          }
      }

**3. (Optional) Changing the amount displayed**

By default, 4 filters are displayed. If you want to show more or less, override getNumberDisplayed in your FilterSet :

```php
public function getNumberDisplayed(): int
{
    return 8;
}
```

**4. (Optional) Add default values**

You can set a default value for your filter. To do this, simply add the setDefault method to your filter :

```php
public function getFilters(): array
{
    return [
        BooleanFilterType::new('actif')->setDefault(['op' => 'eq', 'value' => 'true']),
    ];
}
```

To configure the default values for an `EntityFilterType`, you must first create 2 tables.
One containing the id and text (toString) of each of your default values and the other with the id of each of your default values.
Then, in the `setDefault` method, you need to configure 3 pieces of data:
    - op
    - items (json)
    - value

```php
public function getFilters(): array
{
    $result = $this->em->getRepository(YourEntity::class)->findBy(['code' => ['A', 'B']]);

    $items = [];
    $idItems = [];
    foreach ($results as $result) {
        $items[] = [
            'id' => $result->getId(),
            'text' => $result->getCode() . ' - ' . $result->getLibelle(),
        ];
        $idItems[] = $result->getId();
    }

    return [
        EntityFilterType::new('yourField', YourEntity::class)->setDefault([
            'op' => 'eq',
            'items' => json_encode($items),
            'value' => implode(',', $idItems),
        ]),
    ];
}
```

**Available filter types :**

- StringFilterType : simple string search
- BooleanFilterType : true/false/both values
- ChoiceFilterType : a select
- EntityFilterType : autocomplete select for entities
- DateFilterType : simple dates
- DateTimeFilterType : simple datetimes
- PeriodeFilterType : date ranges
- NumberFilterType : simple number search
- NumberRangeFilterType : number ranges
- TreeFilterType (still WIP) : for Gedmo trees (to include children)
- WorkflowFilterType : for Symfony workflows (see Workflow doc)
