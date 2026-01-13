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
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class LleCruditExtension extends Extension implements ExtensionInterface
{
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
        $container->setParameter('lle_crudit.add_exit_impersonation_button', $processedConfig['add_exit_impersonation_button']);
        $container->setParameter('lle_crudit.exit_impersonation_path', $processedConfig['exit_impersonation_path']);
        $container->setParameter('lle_crudit.generate_default_role', $processedConfig['generate_default_role']);
        $container->setParameter('lle_crudit.css_class_form', $processedConfig['css_class_form']);

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
