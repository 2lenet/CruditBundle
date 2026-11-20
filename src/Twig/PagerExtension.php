<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Twig;

use Lle\CruditBundle\Builder\PagerViewBuilder;
use Lle\CruditBundle\Dto\BrickView;
use Twig\Attribute\AsTwigFunction;

/**
 * `crudit_pager(view)`: the props of the Pager of CruditUiBundle for the view of a list (see PagerViewBuilder).
 */
class PagerExtension
{
    public function __construct(private PagerViewBuilder $builder)
    {
    }

    #[AsTwigFunction('crudit_pager')]
    public function pager(BrickView $view): ?array
    {
        return $this->builder->build($view);
    }
}
