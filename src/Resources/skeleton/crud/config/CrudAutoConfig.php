<?= "<?php" ?>
<?php if ($strictType): ?>

declare(strict_types=1);
<?php endif; ?>

namespace <?= $namespace ?>;

use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Crud\AbstractCrudConfig;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use App\Crudit\Datasource\<?= $entityClass ?>Datasource;

class <?= $entityClass ?>CrudConfig extends AbstractCrudConfig
{
    public function __construct(
        <?= $entityClass ?>Datasource $datasource
    )
    {
        $this->datasource = $datasource;
    }

    /**
    * @param string $key
    * @return Field[]
    */
    public function getFields($key): array
    {
<?php foreach ($fields as $field) { if ($field != 'id') { ?>
        $<?php echo $field['name'] ?> = Field::new('<?php echo $field['name'] ?>')<?php if (!$field['sortable']) { echo '->setSortable(false)'; } ?>;
<?php }} ?>

        // You can return different fields based on the block key
        if ($key == CrudConfigInterface::INDEX || $key == CrudConfigInterface::SHOW) {
            return [
<?php foreach ($fields as $field) { if ($field != 'id') { ?>
               $<?= $field['name'] ?>,
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
