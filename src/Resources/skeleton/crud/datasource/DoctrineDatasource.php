<?= "<?php" ?>
<?php if ($strictType): ?>

declare(strict_types=1);
<?php endif; ?>

namespace <?= $namespace; ?>;

<?php if ($hasFilterset) { ?>use App\Crudit\Datasource\Filterset\<?= $prefixFilename ?>FilterSet;<?php echo "\n"; } ?>
use App\Entity\<?= $entityClass ?>;
use Lle\CruditBundle\Datasource\AbstractDoctrineDatasource;
<?php if ($hasFilterset) { ?>
use Symfony\Contracts\Service\Attribute\Required;
<?php } ?>

class <?= $prefixFilename ?>Datasource extends AbstractDoctrineDatasource
{
    public function getClassName(): string
    {
        return <?= $entityClass ?>::class;
    }
<?php if ($hasFilterset) { ?>

    #[Required]
    public function setFilterset(<?= $prefixFilename ?>FilterSet $filterSet): void
    {
        $this->filterset = $filterSet;
    }
<?php } ?>
}
