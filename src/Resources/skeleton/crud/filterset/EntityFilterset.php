<?= '<?php' ?>

<?php if ($strictType) { echo "\n"; ?>
declare(strict_types=1);
<?php } ?>

namespace <?= $namespace ?>;

use Lle\CruditBundle\Datasource\AbstractFilterSet;
use Lle\CruditBundle\Filter\FilterType\AbstractFilterType;
<?php foreach ($uses as $use) { ?>
use <?= $use ?>;
<?php } ?>
/**
* @return array<int, AbstractFilterType>
*/
class <?= $prefixFilename ?>FilterSet extends AbstractFilterSet
{
    public function getFilters(): array
    {
        return [
<?php foreach ($filters as $filter) { ?>
            <?= $filter['type'] ?>::new('<?= $filter['property'] ?>'<?php if ($filter['options']) { ?>, <?= implode(', ', $filter['options']) ?><?php
            } ?>),
<?php } ?>
        ];
    }
}
