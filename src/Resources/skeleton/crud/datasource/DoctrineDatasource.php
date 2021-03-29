<?= "<?php" ?>
<?php if ($strictType): ?>


declare(strict_types=1);
<?php endif; ?>

namespace <?= $namespace; ?>;

use Lle\CruditBundle\Datasource\AbstractDoctrineDatasource;
use App\Entity\<?= $entityClass ?>;

class <?= $entityClass ?>Datasource extends AbstractDoctrineDatasource
{
    public function getClassName(): string
    {
        return <?= $entityClass ?>::class;
    }
}
