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
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader;

class LleCruditExtension extends Extension implements ExtensionInterface
{
    /** @return void */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
        $loader->load('listeners.yaml');
        $loader->load('bricks.yaml');
        $loader->load('fields.yaml');
        $loader->load('form.yaml');

        $configuration = new Configuration();
        $this->processConfiguration($configuration, $configs);

        $container->registerForAutoconfiguration(MenuProviderInterface::class)->addTag('crudit.menu');
        $container->registerForAutoconfiguration(CrudConfigInterface::class)->addTag('crudit.config');
        $container->registerForAutoconfiguration(DatasourceInterface::class)->addTag('crudit.datasource');
        $container->registerForAutoconfiguration(BrickInterface::class)->addTag('crudit.brick');
        $container->registerForAutoconfiguration(FieldInterface::class)->addTag('crudit.field');
        $container->registerForAutoconfiguration(FilterSetInterface::class)->addTag('crudit.filterset');
        $container->registerForAutoconfiguration(ExporterInterface::class)->addTag("crudit.exporter");

        // Load the templates for the Crudit form types
        if ($container->hasParameter('twig.form.resources')) {
            $container->setParameter('twig.form.resources', array_merge(
                ['@LleCrudit/form/custom_types.html.twig'],
                $container->getParameter('twig.form.resources')
            ));
        }
    }
}
