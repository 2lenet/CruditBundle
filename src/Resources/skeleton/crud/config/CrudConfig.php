<?= "<?php" ?>
<?php if ($strictType): ?>


declare(strict_types=1);
<?php endif; ?>

namespace <?= $namespace ?>;

use Lle\CruditBundle\Brick\ListBrick\ListConfig;
use Lle\CruditBundle\Brick\ShowBrick\ShowConfig;
use Lle\CruditBundle\Contracts\AbstractCrudConfig;
use Lle\CruditBundle\Contracts\DataSourceInterface;
use Lle\CruditBundle\Contracts\MenuProviderInterface;
use Lle\CruditBundle\Dto\Icon;
use Lle\CruditBundle\Dto\Layout\LinkElement;
use Lle\CruditBundle\Dto\Path;
use Symfony\Component\HttpFoundation\Request;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use App\Crudit\Datasource\<?= $entityClass ?>Datasource;

class <?= $entityClass ?>CrudConfig extends AbstractCrudConfig implements MenuProviderInterface
{
    /** @var <?= $entityClass ?>Datasource  */
    private $datasource;

    public function __construct(
        <?= $entityClass ?>Datasource $datasource
    ) {
        $this->datasource = $datasource;
    }

    public function getMenuEntry(): iterable
    {
        yield LinkElement::new(
            '<?= $entityClass ?>s',
            Path::new('app_<?= strtolower($controllerRoute) ?>_index'),
            Icon::new('circle', Icon::TYPE_FAR)
        );
    }

    public function getDatasource(): DataSourceInterface
    {
        return $this->datasource;
    }

    public function getBrickConfigs(Request $request, string $pageKey): iterable
    {
        $bricks = [
            CrudConfigInterface::INDEX => [
                ListConfig::new()->addAuto([<?= join(',', $fields); ?>])
            ],
            CrudConfigInterface::SHOW => [
                ShowConfig::new()->addAuto([<?= join(',', $fields); ?>])
            ]
        ];
        return $bricks[$pageKey];
    }


}
