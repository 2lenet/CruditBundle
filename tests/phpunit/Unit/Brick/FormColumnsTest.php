<?php

declare(strict_types=1);

namespace phpunit\Unit\Brick;

use Lle\CruditBundle\Brick\AbstractBrickConfig;
use Lle\CruditBundle\Brick\FormBrick\FormConfig;
use Lle\CruditBundle\Brick\FormBrick\FormFactory;
use Lle\CruditBundle\Crud\AbstractCrudConfig;
use Lle\CruditBundle\Exception\CruditException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Most fields on a line of a form: the FormConfig, else the CRUD (getFormColumns()), else lle_crudit.form_columns.
 * A CRUD of the 1.x that still overrides getCssClassColumnsForm() is told.
 */
class FormColumnsTest extends TestCase
{
    public function testTheColumnsOfTheProjectByDefault(): void
    {
        self::assertSame(3, $this->columns(FormConfig::new(), $this->crud(new class extends AbstractCrudConfig {
            public function getFields(string $key): array
            {
                return [];
            }

            public function getRootRoute(): string
            {
                return 'app_crud';
            }
        }, 3)));
    }

    public function testTheCrudThenTheFormConfigWin(): void
    {
        $crud = $this->crud(new class extends AbstractCrudConfig {
            public function getFields(string $key): array
            {
                return [];
            }

            public function getRootRoute(): string
            {
                return 'app_crud';
            }

            public function getFormColumns(): int
            {
                return 2;
            }
        });

        self::assertSame(2, $this->columns(FormConfig::new(), $crud));
        self::assertSame(1, $this->columns(FormConfig::new()->setColumns(1), $crud));
    }

    public function testAFormHasOneToFourColumns(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        FormConfig::new()->setColumns(5);
    }

    public function testTheFormerMethodOfTheCrudIsTold(): void
    {
        $crud = $this->crud(new class extends AbstractCrudConfig {
            public function getFields(string $key): array
            {
                return [];
            }

            public function getRootRoute(): string
            {
                return 'app_crud';
            }

            public function getCssClassColumnsForm(): string
            {
                return 'col-md-6';
            }
        });

        $this->expectException(CruditException::class);
        $this->expectExceptionMessageMatches('/getCssClassColumnsForm\(\) is removed in Crudit 2\.0.*getFormColumns\(\)/');
        $this->columns(FormConfig::new(), $crud);
    }

    private function crud(AbstractCrudConfig $crud, int $projectColumns = 4): AbstractCrudConfig
    {
        $crud->setParameterBag(new ParameterBag(['lle_crudit.form_columns' => $projectColumns]));

        return $crud;
    }

    private function columns(FormConfig $config, AbstractCrudConfig $crud): int
    {
        // Not setCrudConfig(): it reads the datasource of the CRUD
        (new \ReflectionProperty(AbstractBrickConfig::class, 'crudConfig'))->setValue($config, $crud);
        $factory = (new \ReflectionClass(FormFactory::class))->newInstanceWithoutConstructor();

        /** @var int $columns */
        $columns = (new \ReflectionMethod(FormFactory::class, 'getColumns'))->invoke($factory, $config);

        return $columns;
    }
}
