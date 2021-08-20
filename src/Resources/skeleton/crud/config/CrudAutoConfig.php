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
use Lle\CruditBundle\Contracts\DatasourceInterface;
use Lle\CruditBundle\Dto\Action\ListAction;
use Lle\CruditBundle\Dto\Action\ItemAction;
use Symfony\Component\HttpFoundation\Request;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use App\Form\<?= $entityClass ?>Type;
use App\Crudit\Datasource\<?= $entityClass ?>Datasource;

class <?= $entityClass ?>CrudConfig extends AbstractCrudConfig
{
    public function __construct(
        <?= $entityClass ?>Datasource $datasource
    )
    {
        $this->datasource = $datasource;
    }

    public function getFields($key): array
    {
<?php foreach ($fields as $field) { if ($field != 'id') { ?>
        $<?php echo $field?> = Field::new('<?php echo $field?>');
<?php }} ?>
        // you can return different fields based on the block key
        if ($key == CrudConfigInterface::INDEX || $key == CrudConfigInterface::SHOW) {
            return [
<?php foreach ($fields as $field) { if ($field != 'id') { ?>
               $<?= $field?>,
<?php }} ?>
            ];
        }

        return [];
    }

    public function getRootRoute(): string
    {
        return 'app_<?= strtolower($controllerRoute) ?>';
    }

}
