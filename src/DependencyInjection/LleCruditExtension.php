<?php

declare(strict_types=1);

namespace Lle\CruditBundle\DependencyInjection;

use Lle\CruditBundle\Layout\LayoutInterface;
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
        $loader->load('serializers.yaml');

        $configuration = new Configuration();
        $processedConfig =  $this->processConfiguration($configuration, $configs);
        $container->setParameter('crudit.layout_provider', $processedConfig[ 'layout_provider' ]);

        $container->registerForAutoconfiguration(LayoutInterface::class)->addTag('crudit.layout');
    }
}
