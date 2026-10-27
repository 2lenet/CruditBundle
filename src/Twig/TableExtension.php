<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Twig;

use Lle\CruditBundle\Builder\TableViewBuilder;
use Lle\CruditBundle\Dto\BrickView;
use Twig\Attribute\AsTwigFunction;

/**
 * `crudit_table(view)`: the data of the Table of CruditUiBundle for the view of a list (see TableViewBuilder).
 */
class TableExtension
{
    public function __construct(private TableViewBuilder $builder)
    {
    }

    #[AsTwigFunction('crudit_table')]
    public function table(BrickView $view): array
    {
        return $this->builder->build($view);
    }
}
