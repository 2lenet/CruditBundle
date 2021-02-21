<?php

declare(strict_types=1);

namespace Lle\CruditBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class CompilerPass implements CompilerPassInterface
{

    /** @return void */
    public function process(ContainerBuilder $container)
    {
        $config = [];
        $parameterName = 'crudit_view';
        $config['globals'][$parameterName] = $container->getParameter('crudit.layout_provider');
        $container->prependExtensionConfig('twig', $config);
    }
}
