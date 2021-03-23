<?= "<?php" ?>
<?php if ($strictType): ?>


declare(strict_types=1);
<?php endif; ?>

namespace <?= $namespace; ?>;

use App\Repository\<?= $entityClass ?>Repository;
use Lle\CruditBundle\Contracts\DataSourceInterface;

class <?= $entityClass ?>Datasource implements DataSourceInterface
{

    /**
     * @var <?= $entityClass ?>Repository
     */
    private $repository;

    public function __construct(<?= $entityClass ?>Repository $repository)
    {
        $this->repository = $repository;
    }


    public function get($id): ?object
    {
        return $this->repository->find($id);
    }

    public function list(): iterable
    {
        return $this->repository->findBy([]);
    }

    public function delete($id): bool
    {
        // TODO: Implement delete() method.
    }

    public function put($id, array $data): ?object
    {
        // TODO: Implement put() method.
    }

    public function patch($id, array $data): ?object
    {
        // TODO: Implement patch() method.
    }
}
