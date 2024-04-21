<?= "<?php" ?>
<?php if ($strictType): ?>

declare(strict_types=1);
<?php endif; ?>

namespace <?= $namespace; ?>;

use App\Crudit\Config\<?= $configSubdirectorie ?><?= $prefixFilename ?>CrudConfig;
use Lle\CruditBundle\Controller\AbstractCrudController;
use Lle\CruditBundle\Controller\TraitCrudController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/<?= strtolower($prefixFilename) ?>')]
class <?= $prefixFilename ?>Controller extends AbstractCrudController
{
    use TraitCrudController;

    public function __construct(<?= $prefixFilename ?>CrudConfig $config)
    {
        $this->config = $config;
    }
}
