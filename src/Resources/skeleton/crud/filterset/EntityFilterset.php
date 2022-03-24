<?= "<?php" ?>
<?php if ($strictType): ?>


declare(strict_types=1);
<?php endif; ?>

namespace <?= $namespace ?>;

use Lle\CruditBundle\Datasource\AbstractFilterSet;
use Lle\CruditBundle\Filter\FilterType\StringFilterType;

class <?= $entityClass ?>FilterSet extends AbstractFilterSet
{
    /**
    * @return array
    */
    public function getFilters(): array
    {
        return [
<?php foreach($fields as $field) { if ($field !='id') { ?>
            StringFilterType::new('<?= $field['name'] ?>'),
<?php }} ?>
        ];
     }
}
