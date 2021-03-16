<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\ListBrick;

use Doctrine\ORM\EntityManagerInterface;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Contracts\BrickInterface;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\RessourceView;
use Lle\CruditBundle\Lib\Pager;
use Lle\CruditBundle\Resolver\RessourceResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\SerializerInterface;

class ListFactory implements BrickInterface
{
    /** @var EntityManagerInterface  */
    protected $entityManager;

    /** @var RessourceResolver  */
    protected $ressourceResolver;

    public function __construct(
        EntityManagerInterface $entityManager,
        RessourceResolver $ressourceResolver,
        RequestStack $requestStack
    ) {
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
        $this->ressourceResolver = $ressourceResolver;
    }

    public function support(BrickConfigInterface $brickConfigurator): bool
    {
        return (ListConfig::class === get_class($brickConfigurator));
    }

    public function buildView(BrickConfigInterface $brickConfigurator): BrickView
    {

        $view = new BrickView(spl_object_hash($brickConfigurator));
        if ($brickConfigurator instanceof ListConfig) {
            //$pager = $this->getPager($brickConfigurator);
            $view
                ->setTemplate('@LleCrudit/brick/list_items')
                ->setConfig($brickConfigurator->getConfig())
                ->setData([
                    //'pager' => $pager->getInfo(),
                    'lines' => $this->getLines($brickConfigurator)
                ]);
        }
        return $view;
    }

    private function getPager(BrickConfigInterface $brickConfigurator): Pager
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
    private function getFields(ListConfig $brickConfigurator): array
    {
        return $brickConfigurator->getFields();
    }

    /** @return RessourceView[] */
    private function getLines(ListConfig $brickConfigurator): array
    {
        $lines = [];
        foreach ($brickConfigurator->getDataSource()->list() as $item) {
            $lines[] = $this->ressourceResolver->resolve($item, $this->getFields($brickConfigurator));
        }
        return $lines;
    }

    
}
