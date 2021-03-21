<?= "<?php" ?>
<?php if ($strictType): ?>


declare(strict_types=1);
<?php endif; ?>

namespace <?= $namespace ?>;

use Lle\CruditBundle\Brick\ListBrick\ListConfig;
use Lle\CruditBundle\Contracts\AbstractCrudAutoConfig;
use Lle\CruditBundle\Contracts\DataSourceInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Crudit\Datasource\<?= $entityClass ?>Datasource;

class <?= $entityClass ?>CrudConfig extends AbstractCrudAutoConfig
{
    /** @var <?= $entityClass ?>Datasource  */
    private $datasource;

    public function __construct(
        <?= $entityClass ?>Datasource $datasource
    ) {
        $this->datasource = $datasource;
    }

    public function getDefaultDatasource(): DataSourceInterface
    {
        return $this->datasource;
    }

    public function getBrickConfigs(Request $request): iterable
    {
        return [
            ListConfig::new()->addAuto([])
        ];
    }


}
