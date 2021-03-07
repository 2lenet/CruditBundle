<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\ListBrick;

use Doctrine\ORM\EntityManagerInterface;
use Lle\CruditBundle\Contracts\BrickConfiguratorInterface;
use Lle\CruditBundle\Contracts\BrickInterface;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\RessourceView;
use Lle\CruditBundle\Lib\Pager;
use Lle\CruditBundle\Resolver\RessourceResolver;
use Symfony\Component\HttpFoundation\Request;

class ListItemBrick implements BrickInterface
{
    /** @var EntityManagerInterface  */
    protected $entityManager;

    /** @var RessourceResolver  */
    protected $ressourceResolver;

    public function __construct(EntityManagerInterface $entityManager, RessourceResolver $ressourceResolver)
    {
        $this->entityManager = $entityManager;
        $this->ressourceResolver = $ressourceResolver;
    }

    public function support(BrickConfiguratorInterface $brickConfigurator): bool
    {
        return (ListItem::class === get_class($brickConfigurator));
    }

    public function bindRequest(Request $request): void
    {
    }

    private function getPager(BrickConfiguratorInterface $brickConfigurator): Pager
    {
        /** @var class-string $className */
        $className = $brickConfigurator->getSubjectClass();
        return new Pager(
            $this->entityManager->getRepository($className)->createQueryBuilder('root'),
            1,
            20,
            false
        );
    }

    /** @return Field[] */
    private function getFields(ListItem $brickConfigurator): array
    {
        return $brickConfigurator->getFields();
    }

    /** @return RessourceView[] */
    private function getLines(ListItem $brickConfigurator, Pager $pager): array
    {
        $lines = [];
        foreach ($pager->getEntities() as $item) {
            $lines[] = $this->ressourceResolver->resolve($item, $this->getFields($brickConfigurator));
        }
        return $lines;
    }

    public function buildView(BrickConfiguratorInterface $brickConfigurator): BrickView
    {

        $view = new BrickView();
        if ($brickConfigurator instanceof ListItem) {
            $pager = $this->getPager($brickConfigurator);
            $view
                ->setTemplate('@LleCrudit/brick/list_items')
                ->setData([
                    'bulk' => false,
                    'fields' => $this->getFields($brickConfigurator),
                    'sort' => ['name' => 'id', 'direction' => 'ASC'],
                    'hideAction' => false,
                    'pager' => $pager->getInfo(),
                    'lines' => $this->getLines($brickConfigurator, $pager),
                    'actions' => [],
                    'detail' => null
                ]);
        }
        return $view;
    }
}
