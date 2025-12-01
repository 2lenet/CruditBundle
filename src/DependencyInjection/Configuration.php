<?php

declare(strict_types=1);

namespace Lle\CruditBundle\DependencyInjection;

use Lle\CruditBundle\Field\AbstractField;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('lle_crudit');
        $rootNode = $treeBuilder->getRootNode();
        $children = $rootNode->children();
        $children
            ->scalarNode('default_currency_alignment')
            ->defaultValue('right')
            ->validate()
                ->ifNotInArray([
                    AbstractField::ALIGN_LEFT,
                    AbstractField::ALIGN_CENTER,
                    AbstractField::ALIGN_RIGHT,
                ])
                ->thenInvalid('Invalid alignment value %s')
            ->end();
        $children
            ->scalarNode('default_integer_alignment')
            ->defaultValue('right')
            ->validate()
                ->ifNotInArray([
                    AbstractField::ALIGN_LEFT,
                    AbstractField::ALIGN_CENTER,
                    AbstractField::ALIGN_RIGHT,
                ])
                ->thenInvalid('Invalid alignment value %s')
            ->end();
        $children
            ->scalarNode('default_number_alignment')
            ->defaultValue('right')
            ->validate()
                ->ifNotInArray([
                    AbstractField::ALIGN_LEFT,
                    AbstractField::ALIGN_CENTER,
                    AbstractField::ALIGN_RIGHT,
                ])
                ->thenInvalid('Invalid alignment value %s')
            ->end();
        $children
            ->scalarNode('hide_if_disabled')
            ->defaultValue(false)
            ->validate()
                ->ifNotInArray([
                    true,
                    false,
                ])
                ->thenInvalid('Invalid value %s')
            ->end();
        $children
            ->scalarNode('delete_hide_if_disabled')
            ->defaultValue(false)
            ->validate()
            ->ifNotInArray([
                true,
                false,
            ])
            ->thenInvalid('Invalid value %s')
            ->end();
        $children
            ->scalarNode('add_connect_profile_link')
            ->defaultValue(false)
            ->validate()
            ->ifNotInArray([
                true,
                false,
            ])
            ->thenInvalid('Invalid value %s')
            ->end();
        $children
            ->scalarNode('add_exit_impersonation_button')
            ->defaultValue(false)
            ->validate()
            ->ifNotInArray([
                true,
                false,
            ])
            ->thenInvalid('Invalid value %s')
            ->end();
        $children
            ->scalarNode('exit_impersonation_path')
            ->defaultValue('homepage')
            ->end();
        $children
            ->scalarNode('generate_default_role')
            ->defaultFalse()
            ->validate()
            ->ifNotInArray([
                true,
                false,
            ])
            ->thenInvalid('Invalid value %s')
            ->end();

        return $treeBuilder;
    }
}
