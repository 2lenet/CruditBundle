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
<?php foreach ($fields as $field) { ?>
        $<?php echo $field->getName() ?> = Field::new("<?php echo $field->getName() ?>");
<?php } ?>

        switch ($key) {
<?php foreach ($cruds as $crud => $crudFields) { ?>
            case <?= $crud ?>:
                $fields = [
<?php foreach ($crudFields as $field) { ?>
                    $<?= $field->getName() ?><?php if (!$field->isSortable()) { echo "->setSortable(false)"; } ?>,
<?php } ?>
                ];
                break;
<?php } ?>
            default:
                $fields = [];
        }

        return $fields;
    }
<?php if (isset($forms)) { ?>
    protected function getFormType(string $pageKey): ?string
    {
        switch ($pageKey) {
<?php foreach ($forms as $key => $formPrefix) { ?>
            case <?= $key ?>:
                $prefix = "<?= $formPrefix ?>";
                break;
<?php } ?>
            default:
                return null;
        }

        return str_replace(
            "App\\Crudit\\Config\\",
            "App\\Form\\" . $prefix,
            str_replace("CrudConfig", "Type", get_class($this))
        );
    }

<?php } ?>

    public function getRootRoute(): string
    {
        return "app_<?= strtolower($controllerRoute) ?>";
    }
}
