<?= '<?php' ?>

<?php if ($strictType) { echo "\n"; ?>
declare(strict_types=1);
<?php } ?>

namespace <?= $namespace; ?>;

<?php if ($hasFilterset) { ?>use App\Crudit\Datasource\Filterset\<?= $configSubdirectorie ?><?= $shortEntityClass ?>FilterSet;<?php echo "\n"; } ?>
use <?= $entityClass ?>;
use Lle\CruditBundle\Datasource\AbstractDoctrineDatasource;
<?php if ($hasFilterset) { ?>
use Symfony\Contracts\Service\Attribute\Required;
<?php } ?>

class <?= $shortEntityClass ?>Datasource extends AbstractDoctrineDatasource
{
    public function getClassName(): string
    {
        return <?= $shortEntityClass ?>::class;
    }
<?php if ($hasFilterset) { ?>

    #[Required]
    public function setFilterset(<?= $shortEntityClass ?>FilterSet $filterSet): void
    {
        $this->filterset = $filterSet;
    }
<?php } ?>
}
