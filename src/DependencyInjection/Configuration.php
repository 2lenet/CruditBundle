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
                ->end()
            ->end()
            ->scalarNode('default_integer_alignment')
                ->defaultValue('right')
                ->validate()
                    ->ifNotInArray([
                        AbstractField::ALIGN_LEFT,
                        AbstractField::ALIGN_CENTER,
                        AbstractField::ALIGN_RIGHT,
                    ])
                    ->thenInvalid('Invalid alignment value %s')
                ->end()
            ->end()
            ->scalarNode('default_number_alignment')
                ->defaultValue('right')
                ->validate()
                    ->ifNotInArray([
                        AbstractField::ALIGN_LEFT,
                        AbstractField::ALIGN_CENTER,
                        AbstractField::ALIGN_RIGHT,
                    ])
                    ->thenInvalid('Invalid alignment value %s')
                ->end()
            ->end()
            ->scalarNode('hide_if_disabled')
                ->defaultValue(false)
                ->validate()
                    ->ifNotInArray([
                        true,
                        false,
                    ])
                    ->thenInvalid('Invalid value %s')
                ->end()
            ->end()
            ->scalarNode('delete_hide_if_disabled')
                ->defaultValue(false)
                ->validate()
                    ->ifNotInArray([
                        true,
                        false,
                    ])
                    ->thenInvalid('Invalid value %s')
                ->end()
            ->end()
            ->scalarNode('add_connect_profile_link')
                ->defaultValue(false)
                ->validate()
                    ->ifNotInArray([
                        true,
                        false,
                    ])
                    ->thenInvalid('Invalid value %s')
                ->end()
            ->end()
            ->scalarNode('add_exit_impersonation_button')
                ->defaultValue(false)
                ->validate()
                    ->ifNotInArray([
                        true,
                        false,
                    ])
                    ->thenInvalid('Invalid value %s')
                ->end()
            ->end()
            ->scalarNode('exit_impersonation_path')
                ->defaultValue('homepage')
            ->end()
            ->scalarNode('generate_default_role')
                ->defaultValue(false)
                ->validate()
                    ->ifNotInArray([
                        true,
                        false,
                    ])
                    ->thenInvalid('Invalid value %s')
                ->end()
            ->end()
            ->arrayNode('ignore_referer_routes')
                ->acceptAndWrap(['string'])
                ->scalarPrototype()
                ->end()
            ->end()
            ->scalarNode('css_class_columns_form')
                ->defaultValue('col-sm-12 col-md-6 col-lg-4')
            ->end()
            ->scalarNode('css_class_columns_show')
                ->defaultValue('col-sm-12 col-md-6 col-lg-4')
            ->end()
            ->scalarNode('css_class_columns_card')
                ->defaultValue('col-sm-12 col-md-6 col-lg-4')
            ->end()
            ->scalarNode('number_cards_per_row')
                ->defaultValue(1)
            ->end()
            // Global search of the layout (lot 2.2.c): the entities asked, never all those that have an autocomplete.
            ->arrayNode('search')
                ->addDefaultsIfNotSet()
                ->children()
                    ->booleanNode('enabled')
                        ->info('Shows the global search (button of the header, Ctrl K) when its route is imported')
                        ->defaultTrue()
                    ->end()
                    ->integerNode('min_length')
                        ->info('Characters to type before the first search')
                        ->defaultValue(2)
                        ->min(1)
                    ->end()
                    ->integerNode('limit')
                        ->info('Results per group of entities, unless the entity gives its own (the group of the screens has 5)')
                        ->defaultValue(10)
                        ->min(1)
                        ->max(20)
                    ->end()
                    ->booleanNode('screens')
                        ->info('Adds the group of the screens: the links of the menu that the user sees')
                        ->defaultTrue()
                    ->end()
                    ->arrayNode('entities')
                        ->info('CrudConfig searched, in the order of the groups: by their autocomplete (the search fields of their datasource), for a user granted ROLE_<NAME>_SHOW')
                        ->arrayPrototype()
                            ->beforeNormalization()
                                ->ifString()
                                ->then(static fn (string $config): array => ['config' => $config])
                            ->end()
                            ->children()
                                ->scalarNode('config')
                                    ->info('Class of the CrudConfig')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                ->end()
                                ->scalarNode('title')
                                    ->info('Title of the group (translation key or text); the title of the list of the CrudConfig without it')
                                    ->defaultNull()
                                ->end()
                                ->integerNode('limit')
                                    ->info('Results of the group; lle_crudit.search.limit without it')
                                    ->defaultNull()
                                    ->min(1)
                                    ->max(20)
                                ->end()
                                ->scalarNode('subtitle')
                                    ->info('Property path (PropertyAccess) of the second line of a result: "city", "customer.name"')
                                    ->defaultNull()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            // Logo, favicon and footer of the layout (lot 2.1.c). The images are given to asset(): a logical path of
            // AssetMapper (images/logo.svg), a public path (/img/logo.png) or an address.
            ->arrayNode('layout')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('logo')
                        ->info('Logo of the menu, light theme (and dark theme without logo_dark)')
                        ->defaultNull()
                    ->end()
                    ->scalarNode('logo_dark')
                        ->info('Logo of the menu, dark theme')
                        ->defaultNull()
                    ->end()
                    ->scalarNode('logo_small')
                        ->info('Square logo of the collapsed menu (rail); the initial of app_name without it')
                        ->defaultNull()
                    ->end()
                    ->scalarNode('favicon')
                        ->defaultNull()
                    ->end()
                    ->scalarNode('version')
                        ->info('Version shown in the footer (%env(APP_VERSION)%); the Twig global app_version without it')
                        ->defaultNull()
                    ->end()
                    ->scalarNode('release_date')
                        ->info('Date of the version, shown in the footer (Y-m-d)')
                        ->defaultNull()
                    ->end()
                    ->arrayNode('footer_links')
                        ->info('Links of the footer (accessibility statement, legal notice…): none by default')
                        ->arrayPrototype()
                            ->children()
                                ->scalarNode('label')
                                    ->info('Translation key (domain messages) or text')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                ->end()
                                ->scalarNode('route')->defaultNull()->end()
                                ->arrayNode('route_params')
                                    ->variablePrototype()->end()
                                ->end()
                                ->scalarNode('url')->defaultNull()->end()
                            ->end()
                            ->validate()
                                ->ifTrue(static fn (array $link): bool => ($link['route'] === null) === ($link['url'] === null))
                                ->thenInvalid('A link of the footer needs either a route or an url: %s')
                            ->end()
                            ->validate()
                                ->ifTrue(static fn (array $link): bool => $link['url'] !== null && $link['route_params'] !== [])
                                ->thenInvalid('A link of the footer with an url has no route_params: %s')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->validate()
                    ->ifTrue(static fn (array $layout): bool => $layout['logo_dark'] !== null && $layout['logo'] === null)
                    ->thenInvalid('lle_crudit.layout.logo_dark needs lle_crudit.layout.logo: %s')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
