<?= "<?php" ?>
<?php if ($strictType): ?>


declare(strict_types=1);
<?php endif; ?>

namespace <?= $namespace; ?>;

use App\Crudit\Datasource\Filterset\<?= $entityClass ?>FilterSet;
use App\Entity\<?= $entityClass ?>;
use Lle\CruditBundle\Datasource\AbstractDoctrineDatasource;

class <?= $entityClass ?>Datasource extends AbstractDoctrineDatasource
{
    public function getClassName(): string
    {
        return <?= $entityClass ?>::class;
    }
<?php if($hasFilterset) { ?>
    /**
    * @required
    */
    public function setFilterset(<?= $entityClass ?>FilterSet $filterSet)
    {
         $this->filterset = $filterSet;
    }<?php } ?>
}
