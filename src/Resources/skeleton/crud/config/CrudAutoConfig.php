<?= '<?php' ?>

<?php if ($strictType) { echo "\n"; ?>
declare(strict_types=1);
<?php } ?>

namespace <?= $namespace ?>;

use App\Crudit\Datasource\<?= $configSubdirectorie ?><?= $prefixFilename ?>Datasource;
<?php if (array_key_exists('history', $tabs) && count($tabs['history'])) { ?>
use Lle\CruditBundle\Brick\HistoryBrick\HistoryConfig;
<?php } ?>
<?php if (array_key_exists('sublist', $tabs) && count($tabs['sublist'])) { ?>
use Lle\CruditBundle\Brick\SublistBrick\SublistConfig;
<?php } ?>
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Crud\AbstractCrudConfig;
<?php if ((isset($itemActions) && count($itemActions)) || (isset($showActions) && count($showActions))) { ?>
use Lle\CruditBundle\Dto\Action\ItemAction;
<?php } ?>
<?php if (isset($listActions) && count($listActions)) { ?>
use Lle\CruditBundle\Dto\Action\ListAction;
<?php } ?>
use Lle\CruditBundle\Dto\Field\Field;
<?php if ((isset($listActions) && count($listActions)) || (isset($itemActions) && count($itemActions)) || (isset($showActions) && count($showActions))) { ?>
use Lle\CruditBundle\Dto\Icon;
use Lle\CruditBundle\Dto\Path;
<?php } ?>

class <?= $prefixFilename ?>CrudConfig extends AbstractCrudConfig
{
    public function __construct(
<?php $allLinkedEntity = []; ?>
<?php if (array_key_exists('sublist', $tabs)) { ?>
<?php foreach ($tabs['sublist'] as $tab) { ?>
<?php if (!in_array($tab['linkedEntity'], $allLinkedEntity)) { ?>
        protected <?= $tab['linkedEntity'] ?>CrudConfig $<?= lcfirst($tab['linkedEntity']) ?>CrudConfig,
<?php $allLinkedEntity[] = $tab['linkedEntity'] ?>
<?php } ?>
<?php } ?>
<?php } ?>
        <?= $prefixFilename ?>Datasource $datasource
    ) {
        $this->datasource = $datasource;
    }

    /**
    * @return Field[]
    */
    public function getFields(string $key): array
    {
<?php foreach ($fields as $field) { ?>
<?php if (is_array($field)) { ?>
<?php foreach ($field as $value) { ?>
        $<?php echo str_replace('.', '', $value->getName()) ?> = Field::new('<?php echo $value->getName() ?>');
<?php } ?>
<?php } else { ?>
        $<?php echo str_replace('.', '', $field->getName()) ?> = Field::new('<?php echo $field->getName() ?>');
<?php } ?>
<?php } ?>

        switch ($key) {
<?php foreach ($cruds as $crud => $crudFields) { ?>
            case <?= $crud ?>:
                $fields = [
<?php foreach ($crudFields as $key => $field) { ?>
<?php if (is_array($field)) { ?>
                    '<?= $key ?>' => [
<?php foreach ($field as $value) { ?>
                        $<?= str_replace('.', '', $value->getName()) ?><?php if (!$value->isSortable()) { echo '->setSortable(false)'; } ?>,
<?php } ?>
                    ],
<?php } else { ?>
                    $<?= str_replace('.', '', $field->getName()) ?><?php if (!$field->isSortable()) { echo '->setSortable(false)'; } ?>,
<?php } ?>
<?php } ?>
                ];
                break;
<?php } ?>
            default:
                $fields = [];
        }

        return $fields;
    }
<?php if (isset($forms) && count($forms)) { ?>

    protected function getFormType(string $pageKey): ?string
    {
        switch ($pageKey) {
<?php foreach ($forms as $key => $formPrefix) { ?>
            case <?= $key ?>:
                $prefix = '<?= $formPrefix ?>';
                break;
<?php } ?>
            default:
                return null;
        }

        return str_replace(
            'App\\Crudit\\Config\\',
            'App\\Form\\' . $prefix,
            str_replace('CrudConfig', 'Type', get_class($this))
        );
    }
<?php } ?>
<?php if (array_key_exists('sublist', $tabs)) { ?>
<?php if ((isset($tabs['sublist']) && count($tabs['sublist'])) || (isset($tabs['history']) && count($tabs['history']))) { ?>

    public function getTabs(): array
    {
        return [
<?php foreach ($tabs['sublist'] as $tab) { ?>
            '<?= $tab['label'] ?>' => [SublistConfig::new('<?= $tab['property'] ?>', $this-><?= lcfirst($tab['linkedEntity']) ?>CrudConfig)
                ->setFields($this-><?= lcfirst($tab['linkedEntity']) ?>CrudConfig->getFields(CrudConfigInterface::INDEX))],
<?php } ?>
<?php } ?>
<?php if (array_key_exists('history', $tabs)) { ?>
<?php foreach ($tabs['history'] as $tab) { ?>
            '<?= $tab['label'] ?>' => [HistoryConfig::new()],
<?php } ?>
<?php } ?>
        ];
    }
<?php } ?>
<?php if ((isset($listActions) && count($listActions)) || (isset($disabledActions['list']) && count($disabledActions['list']))) { ?>

        public function getListActions(): array
        {
            $actions = parent::getListActions();
<?php if (isset($disabledActions['list'])) { ?>
<?php foreach ($disabledActions['list'] as $key => $action) { ?>
<?php if ($action === 'new') {
    $action = 'add';
} ?>
<?php if ($key === array_key_first($disabledActions['list'])) { ?>

<?php } ?>
        unset($actions[CrudConfigInterface::ACTION_<?= strtoupper($action) ?>]);
<?php } ?>
<?php } ?>
<?php foreach ($listActions as $action) { ?>

        $actions[] = ListAction::new(
            '<?= $action['label'] ?>',
            <?php if ($action['path']) { ?>Path::new('<?= $action['path'] ?>'),<?php } else { ?>null,<?php } ?>
            <?php if ($action['icon']) { ?>Icon::new('<?= $action['icon'] ?>'),<?php } else { ?>null,<?php } ?>
        )<?php if ($action['cssClass']) { ?>->setCssClass('<?= $action['cssClass'] ?>')<?php } ?><?php if ($action['target']) { ?>->setTarget('<?= $action['target'] ?>')<?php } ?>;
<?php } ?>

        return $actions;
    }
<?php } ?>
<?php if ((isset($itemActions) && count($itemActions)) || (isset($disabledActions['item']) && count($disabledActions['item']))) { ?>

        public function getItemActions(): array
        {
            $actions = parent::getItemActions();
<?php if (isset($disabledActions['item'])) { ?>
<?php foreach ($disabledActions['item'] as $key => $action) { ?>
<?php if ($key === array_key_first($disabledActions['item'])) { ?>

<?php } ?>
        unset($actions[CrudConfigInterface::ACTION_<?= strtoupper($action) ?>]);
<?php } ?>
<?php } ?>
<?php foreach ($itemActions as $action) { ?>

        $actions[] = ItemAction::new(
            '<?= $action['label'] ?>',
            Path::new('<?= $action['path'] ?>'),
            Icon::new('<?= $action['icon'] ?>'),
        )<?php if ($action['cssClass']) { ?>->setCssClass('<?= $action['cssClass'] ?>')<?php } ?><?php if ($action['target']) { ?>->setTarget('<?= $action['target'] ?>')<?php } ?>;
<?php } ?>

        return $actions;
    }
<?php } ?>
<?php if ((isset($showActions) && count($showActions)) || (isset($disabledActions['show']) && count($disabledActions['show']))) { ?>

    public function getShowActions(): array
    {
        $actions = parent::getShowActions();
<?php if (isset($disabledActions['show'])) { ?>
<?php foreach ($disabledActions['show'] as $key => $action) { ?>
<?php if ($key === array_key_first($disabledActions['show'])) { ?>

<?php } ?>
        unset($actions[CrudConfigInterface::ACTION_<?= strtoupper($action) ?>]);
<?php } ?>
<?php } ?>
<?php foreach ($showActions as $action) { ?>

        $actions[] = ItemAction::new(
            '<?= $action['label'] ?>',
            Path::new('<?= $action['path'] ?>'),
            Icon::new('<?= $action['icon'] ?>'),
        )<?php if ($action['cssClass']) { ?>->setCssClass('<?= $action['cssClass'] ?>')<?php } ?><?php if ($action['target']) { ?>->setTarget('<?= $action['target'] ?>')<?php } ?>;
<?php } ?>

        return $actions;
    }
<?php } ?>
<?php if (isset($sort['property']) && isset($sort['order'])) { ?>

    public function getDefaultSort(): array
    {
        return [['<?= $sort['property'] ?>', '<?= $sort['order'] ?>']];
    }
<?php } ?>

    public function getRootRoute(): string
    {
        return 'app_crudit_<?= strtolower($controllerRoute) ?>';
    }
}
