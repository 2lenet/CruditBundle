<?= "<?php" ?>
<?php
if ($strictType): ?>


    declare(strict_types=1);
<?php
endif; ?>

namespace <?= $namespace ?>;

use Lle\CruditBundle\Datasource\AbstractFilterSet;
<?php
foreach ($uses as $use) { ?>
    use <?= $use ?>;
<?php
} ?>

class <?= $prefixFilename ?>FilterSet extends AbstractFilterSet
{
public function getFilters(): array
{
return [
<?php
foreach ($filters as $filter) { ?>
    <?= $filter["type"] ?>::new("<?= $filter["property"] ?>"<?php
    if ($filter["options"]) { ?>, <?= implode(", ", $filter["options"]) ?><?php
    } ?>),
<?php
} ?>
];
}
}
