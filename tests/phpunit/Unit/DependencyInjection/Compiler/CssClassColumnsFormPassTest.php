<?php

declare(strict_types=1);

namespace phpunit\Unit\DependencyInjection\Compiler;

use Lle\CruditBundle\DependencyInjection\Compiler\CssClassColumnsFormPass;
use Lle\CruditBundle\Exception\CruditException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * A CrudConfig of the 1.x that still overrides getCssClassColumnsForm() is refused when the container is built, all of
 * them in one message.
 */
class CssClassColumnsFormPassTest extends TestCase
{
    public function testACrudConfigWithoutTheFormerMethodPasses(): void
    {
        (new CssClassColumnsFormPass())->process($this->container([CssClassColumnsFormPassTestNewCrud::class, 'App\\GhostCrudConfig']));

        $this->addToAssertionCount(1);
    }

    public function testEveryCrudConfigWithTheFormerMethodIsNamed(): void
    {
        $container = $this->container([CssClassColumnsFormPassTestNewCrud::class, CssClassColumnsFormPassTestOldCrud::class, CssClassColumnsFormPassTestOtherOldCrud::class]);

        $this->expectException(CruditException::class);
        $this->expectExceptionMessage(sprintf('getCssClassColumnsForm() is removed in Crudit 2.0 (Bootstrap classes), still in %s, %s: return the number of columns of the forms (1 to 4) from getFormColumns() instead', CssClassColumnsFormPassTestOldCrud::class, CssClassColumnsFormPassTestOtherOldCrud::class));
        (new CssClassColumnsFormPass())->process($container);
    }

    public function testTheClassOfAServiceIsAParameter(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('app.crud.class', CssClassColumnsFormPassTestOldCrud::class);
        $container->setDefinition('app.crud', (new Definition('%app.crud.class%'))->addTag('crudit.config'));

        $this->expectException(CruditException::class);
        (new CssClassColumnsFormPass())->process($container);
    }

    /**
     * @param list<string> $configs
     */
    private function container(array $configs): ContainerBuilder
    {
        $container = new ContainerBuilder();
        foreach ($configs as $config) {
            $container->setDefinition($config, (new Definition($config))->addTag('crudit.config'));
        }

        return $container;
    }
}

class CssClassColumnsFormPassTestNewCrud
{
    public function getFormColumns(): int
    {
        return 2;
    }
}

class CssClassColumnsFormPassTestOldCrud
{
    public function getCssClassColumnsForm(): string
    {
        return 'col-md-6';
    }
}

class CssClassColumnsFormPassTestOtherOldCrud extends CssClassColumnsFormPassTestOldCrud
{
}
