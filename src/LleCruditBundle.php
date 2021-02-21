<?php

declare(strict_types=1);

namespace Lle\CruditBundle;

use Lle\CruditBundle\DependencyInjection\Compiler\CompilerPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class LleCruditBundle extends Bundle
{

    /** @return void */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new CompilerPass());
    }
}
