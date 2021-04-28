<?= "<?php" ?>
<?php if ($strictType): ?>


declare(strict_types=1);
<?php endif; ?>

namespace <?= $namespace ?>;

use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Brick\LinksBrick\LinksConfig;
use Lle\CruditBundle\Brick\ListBrick\ListConfig;
use Lle\CruditBundle\Brick\ShowBrick\ShowConfig;
use Lle\CruditBundle\Brick\FormBrick\FormConfig;
use Lle\CruditBundle\Crud\AbstractCrudConfig;
use Lle\CruditBundle\Contracts\DataSourceInterface;
use Lle\CruditBundle\Dto\Action\ListAction;
use Lle\CruditBundle\Dto\Action\ItemAction;
use Symfony\Component\HttpFoundation\Request;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
<?php if($form): ?>use App\Form\<?= $entityClass ?>Type;<?php endif; ?>
use App\Crudit\Datasource\<?= $entityClass ?>Datasource;

class <?= $entityClass ?>CrudConfig extends AbstractCrudConfig
{
    /** @var <?= $entityClass ?>Datasource  */
    private $datasource;

    public function __construct(
        <?= $entityClass ?>Datasource $datasource
    ) {
        $this->datasource = $datasource;
    }

    public function getDatasource(): DataSourceInterface
    {
        return $this->datasource;
    }

    public function getFields($key): array
    {
<?php foreach ($fields as $field) { ?>
        $<?php echo $field?> = Field::new('<?php echo $field?>');
<?php } ?>
        // you can return different fields based on the block key
        if ($key == CrudConfigInterface::INDEX || $key == CrudConfigInterface::SHOW) {
            return [
<?php foreach ($fields as $field) { ?>
              $<?= $field?>,
<?php } ?>
            ];
        }
        return [];
    }

    public function getRootRoute(): string
    {
        return 'app_<?= strtolower($controllerRoute) ?>';
    }

    /* can be surcharged 
    public function getBrickConfigs(): array
    {
        return [
            CrudConfigInterface::INDEX => [
                LinksConfig::new()->addAction(ListAction::new('add', $this->getPath(CrudConfigInterface::NEW))),
                ListConfig::new()->addFields($this->getFields(CrudConfigInterface::INDEX))
                    ->addAction(ItemAction::new('show', $this->getPath(CrudConfigInterface::SHOW)))
                    ->addAction(ItemAction::new('edit', $this->getPath(CrudConfigInterface::EDIT)))
            ],
            CrudConfigInterface::SHOW => [
                LinksConfig::new()->addBack(),
                ShowConfig::new()->addFields($this->getFields(CrudConfigInterface::SHOW))
            ],
                CrudConfigInterface::EDIT => [
                LinksConfig::new()->addBack(),
<?php if($form): ?>
                FormConfig::new()->setForm(<?= $entityClass ?>Type::class)
<?php else: ?>
                FormConfig::new()->addAuto([<?= join(',', $fields); ?>]
<?php endif; ?>
            ],
            CrudConfigInterface::NEW => [
                LinksConfig::new()->addBack(),
<?php if($form): ?>
                FormConfig::new()->setForm(<?= $entityClass ?>Type::class)
<?php else: ?>
                FormConfig::new()->addAuto([<?= join(',', $fields); ?>]
<?php endif; ?>
            ]
        ];
    }
    */
}
