<?php

declare(strict_types=1);

namespace phpunit\Unit\DependencyInjection\Compiler;

use Lle\CruditBundle\DependencyInjection\Compiler\RemovedRecordMethodsPass;
use Lle\CruditBundle\Exception\CruditException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * A CrudConfig of the 1.x that still overrides getCssClassColumnsShow(), getCssClassColumnsCard() or
 * getNumberCardsPerRow() is refused when the container is built, all of them in one message with what to do instead.
 */
class RemovedRecordMethodsPassTest extends TestCase
{
    public function testACrudConfigWithoutTheFormerMethodsPasses(): void
    {
        (new RemovedRecordMethodsPass())->process($this->container([RemovedRecordMethodsPassTestNewCrud::class, 'App\\GhostCrudConfig']));

        $this->addToAssertionCount(1);
    }

    public function testEveryFormerMethodIsNamed(): void
    {
        $container = $this->container([
            RemovedRecordMethodsPassTestNewCrud::class,
            RemovedRecordMethodsPassTestOldCrud::class,
            RemovedRecordMethodsPassTestCardCrud::class,
        ]);

        $this->expectException(CruditException::class);
        $this->expectExceptionMessage(sprintf(
            'Methods of the record removed in Crudit 2.0, still in %1$s::getCssClassColumnsShow(), %2$s::getCssClassColumnsShow(),'
            . ' %2$s::getCssClassColumnsCard(): return the number of fields per line from getShowColumns() or getShowSectionColumns(),'
            . ' and widen a field with its option span; %2$s::getNumberCardsPerRow(): the sections of a record are in 2 columns on'
            . ' a wide screen as soon as there are 2, a section takes the whole line with getShowWideSections(); see UPGRADE-2.0.md.',
            RemovedRecordMethodsPassTestOldCrud::class,
            RemovedRecordMethodsPassTestCardCrud::class,
        ));
        (new RemovedRecordMethodsPass())->process($container);
    }

    public function testTheClassOfAServiceIsAParameter(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('app.crud.class', RemovedRecordMethodsPassTestOldCrud::class);
        $container->setDefinition('app.crud', (new Definition('%app.crud.class%'))->addTag('crudit.config'));

        $this->expectException(CruditException::class);
        (new RemovedRecordMethodsPass())->process($container);
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

class RemovedRecordMethodsPassTestNewCrud
{
    public function getShowColumns(): int
    {
        return 2;
    }

    /**
     * @return list<string>
     */
    public function getShowWideSections(): array
    {
        return ['section.history'];
    }
}

class RemovedRecordMethodsPassTestOldCrud
{
    public function getCssClassColumnsShow(): string
    {
        return 'col-md-6';
    }
}

class RemovedRecordMethodsPassTestCardCrud extends RemovedRecordMethodsPassTestOldCrud
{
    public function getCssClassColumnsCard(): string
    {
        return 'col-md-4';
    }

    public function getNumberCardsPerRow(): int
    {
        return 2;
    }
}
