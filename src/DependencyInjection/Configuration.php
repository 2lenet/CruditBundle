<?php

declare(strict_types=1);

namespace Lle\CruditBundle\DependencyInjection;

use Lle\CruditBundle\Layout\AdminLteLayout;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('lle_crudit');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children();
        return $treeBuilder;
    }
}
