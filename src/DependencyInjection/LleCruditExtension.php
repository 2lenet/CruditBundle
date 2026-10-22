<?php

declare(strict_types=1);

namespace Lle\CruditBundle\DependencyInjection;

use Lle\CruditBundle\Contracts\BrickInterface;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Contracts\DatasourceInterface;
use Lle\CruditBundle\Contracts\ExporterInterface;
use Lle\CruditBundle\Contracts\FieldInterface;
use Lle\CruditBundle\Contracts\FilterSetInterface;
use Lle\CruditBundle\Contracts\MenuProviderInterface;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Extension\Extension;

class LleCruditExtension extends Extension implements ExtensionInterface, PrependExtensionInterface
{
    /**
     * Name of the Symfony UX package of the bundle (assets/package.json): the Stimulus controllers and crudit.css.
     */
    public const string ASSETS_PACKAGE = '@2lenet/crudit-bundle';

    /**
     * Under AssetMapper, assets/dist of the bundle is served as @2lenet/crudit-bundle (crudit.css, the controllers).
     * Under Webpack Encore, the project declares the package in its package.json (UPGRADE-2.0.md, §1).
     */
    public function prepend(ContainerBuilder $container): void
    {
        if (!interface_exists(AssetMapperInterface::class)) {
            return;
        }

        /** @var array<string, array{path: string}> $bundlesMetadata */
        $bundlesMetadata = $container->getParameter('kernel.bundles_metadata');
        if (
            !isset($bundlesMetadata['FrameworkBundle'])
            || !is_file($bundlesMetadata['FrameworkBundle']['path'] . '/Resources/config/asset_mapper.php')
        ) {
            return;
        }

        $container->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [
                    \dirname(__DIR__, 2) . '/assets/dist' => self::ASSETS_PACKAGE,
                ],
            ],
        ]);
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
        $loader->load('listeners.yaml');
        $loader->load('bricks.yaml');
        $loader->load('fields.yaml');
        $loader->load('form.yaml');

        $configuration = new Configuration();
        $processedConfig = $this->processConfiguration($configuration, $configs);

        $container->registerForAutoconfiguration(MenuProviderInterface::class)->addTag('crudit.menu');
        $container->registerForAutoconfiguration(CrudConfigInterface::class)->addTag('crudit.config');
        $container->registerForAutoconfiguration(DatasourceInterface::class)->addTag('crudit.datasource');
        $container->registerForAutoconfiguration(BrickInterface::class)->addTag('crudit.brick');
        $container->registerForAutoconfiguration(FieldInterface::class)->addTag('crudit.field');
        $container->registerForAutoconfiguration(FilterSetInterface::class)->addTag('crudit.filterset');
        $container->registerForAutoconfiguration(ExporterInterface::class)->addTag("crudit.exporter");

        $container->setParameter('lle_crudit.default_currency_alignment', $processedConfig['default_currency_alignment']);
        $container->setParameter('lle_crudit.default_integer_alignment', $processedConfig['default_integer_alignment']);
        $container->setParameter('lle_crudit.default_number_alignment', $processedConfig['default_number_alignment']);
        $container->setParameter('lle_crudit.hide_if_disabled', $processedConfig['hide_if_disabled']);
        $container->setParameter('lle_crudit.delete_hide_if_disabled', $processedConfig['delete_hide_if_disabled']);
        $container->setParameter('lle_crudit.add_connect_profile_link', $processedConfig['add_connect_profile_link']);
        $container->setParameter('lle_crudit.exit_impersonation_path', $processedConfig['exit_impersonation_path']);
        $container->setParameter('lle_crudit.generate_default_role', $processedConfig['generate_default_role']);
        $container->setParameter('lle_crudit.ignore_referer_routes', $processedConfig['ignore_referer_routes']);
        $container->setParameter('lle_crudit.css_class_columns_form', $processedConfig['css_class_columns_form']);
        $container->setParameter('lle_crudit.css_class_columns_show', $processedConfig['css_class_columns_show']);
        $container->setParameter('lle_crudit.css_class_columns_card', $processedConfig['css_class_columns_card']);
        $container->setParameter('lle_crudit.number_cards_per_row', $processedConfig['number_cards_per_row']);
        $container->setParameter('lle_crudit.layout', $processedConfig['layout']);
        $search = $processedConfig['search'];
        $search['entities'] = array_map(
            static fn (array $entity): array => [...$entity, 'limit' => $entity['limit'] ?? $search['limit']],
            $search['entities'],
        );
        $container->setParameter('lle_crudit.search.enabled', $search['enabled']);
        $container->setParameter('lle_crudit.search.min_length', $search['min_length']);
        $container->setParameter('lle_crudit.search.limit', $search['limit']);
        $container->setParameter('lle_crudit.search.screens', $search['screens']);
        $container->setParameter('lle_crudit.search.entities', $search['entities']);

        // Load the templates for the Crudit form types
        if ($container->hasParameter('twig.form.resources')) {
            /** @var array $parameter */
            $parameter = $container->getParameter('twig.form.resources');

            $container->setParameter(
                'twig.form.resources',
                array_merge(
                    ['@LleCrudit/form/custom_types.html.twig'],
                    $parameter
                )
            );
        }
    }
}
