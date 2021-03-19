<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\ListBrick;

use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Contracts\BrickInterface;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\RessourceView;
use Lle\CruditBundle\Resolver\RessourceResolver;
use Symfony\Component\HttpFoundation\RequestStack;

class ListFactory implements BrickInterface
{
    /** @var RessourceResolver  */
    protected $ressourceResolver;

    /** @var RequestStack  */
    protected $requestStack;

    public function __construct(
        RessourceResolver $ressourceResolver,
        RequestStack $requestStack
    ) {
        $this->requestStack = $requestStack;
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
            $view
                ->setTemplate('@LleCrudit/brick/list_items')
                ->setConfig($brickConfigurator->getConfig())
                ->setData([
                    'lines' => $this->getLines($brickConfigurator)
                ]);
        }
        return $view;
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
        if ($brickConfigurator->getDataSource() !== null) {
            foreach ($brickConfigurator->getDataSource()->list() as $item) {
                $lines[] = $this->ressourceResolver->resolve($item, $this->getFields($brickConfigurator));
            }
        }
        return $lines;
    }
}
