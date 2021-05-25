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


**The supported filter types are:** 
- StringFilterType
- NumberFilterType
- BooleanFilterType
- ChoiceFilterType
- EntityFilterType
