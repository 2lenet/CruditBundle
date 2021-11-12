# Filters

How to make filters on lists

**1. In the DataSource.php file linked to your entity**

    /**
     * @required
     */
    public function setFilterset(CollecteFilterset $filterset)
    {
        $this->filterset = $filterset;
    }

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
