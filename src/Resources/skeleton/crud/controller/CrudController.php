<?= "<?php" ?>
<?php if ($strictType): ?>


declare(strict_types=1);
<?php endif; ?>

namespace <?= $namespace; ?>;

use App\Crudit\Config\<?= $entityClass ?>CrudConfig;
use Symfony\Component\Routing\Annotation\Route;
use Lle\CruditBundle\Controller\AbstractCrudController;
use Lle\CruditBundle\Controller\TraitCrudController;

/**
 * @Route("/<?= strtolower($entityClass) ?>")
 */
class <?= $entityClass ?>Controller extends AbstractCrudController
{
    use TraitCrudController;

    public function __construct(<?= $entityClass ?>CrudConfig $config)
    {
        $this->config = $config;
    }

}
