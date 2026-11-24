<?php

declare(strict_types=1);

namespace phpunit\Unit\Controller;

use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Contracts\ExporterInterface;
use Lle\CruditBundle\Controller\AbstractCrudController;
use Lle\CruditBundle\Controller\TraitCrudController;
use Lle\CruditBundle\Dto\Action\DropdownAction;
use Lle\CruditBundle\Dto\Action\ListAction;
use Lle\CruditBundle\Dto\Path;
use Lle\CruditBundle\Exporter\CsvExporter;
use Lle\CruditBundle\Exporter\ExcelExporter;
use Lle\CruditBundle\Exporter\Exporter;
use Lle\CruditBundle\Exporter\ExportParams;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The formats that the controller of the export accepts: those of the buttons of the list that the user may use.
 */
class ExportFormatsTest extends TestCase
{
    private function exporter(): Exporter
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $xml = new class () implements ExporterInterface {
            public function getSupportedFormat(): string
            {
                return 'xml';
            }

            public function getFileExtension(): string
            {
                return '.xml';
            }

            public function export(iterable $resources, ExportParams $params, array $totals = []): string
            {
                return '';
            }

            public function getContentType(): string
            {
                return 'application/xml';
            }
        };

        return new Exporter([new CsvExporter($translator), new ExcelExporter($translator), $xml]);
    }

    /**
     * @param list<mixed> $actions the list actions of the config
     * @param list<string> $roles the roles the user has
     *
     * @return list<string>
     */
    private function formats(array $actions, array $roles = []): array
    {
        $config = $this->createStub(CrudConfigInterface::class);
        $config->method('getPath')->willReturn(Path::new('app_client_EXPORT'));
        $config->method('getListActions')->willReturn($actions);

        $controller = new class ($config, $roles) extends AbstractCrudController {
            use TraitCrudController;

            public function __construct(CrudConfigInterface $config, private array $roles)
            {
                $this->config = $config;
            }

            protected function isGranted(mixed $attribute, mixed $subject = null): bool
            {
                return in_array($attribute, $this->roles, true);
            }

            public function formats(Exporter $exporter): array
            {
                return $this->exportFormats($exporter);
            }
        };

        return $controller->formats($this->exporter());
    }

    private function button(string $label, array $formats, ?string $role = null): ListAction
    {
        $action = ListAction::new($label, Path::new('app_client_EXPORT'))->setConfig(['export' => $formats]);

        return $role === null ? $action : $action->setRole($role);
    }

    public function testNoActionOfExportAtAllGivesTheFormatsOfTheBundle(): void
    {
        self::assertSame(['csv', 'xls'], $this->formats([ListAction::new('add', Path::new('app_client_NEW'))]));
    }

    public function testEveryButtonCountsWhateverItsKeyAndItsGroup(): void
    {
        $actions = [
            'export' => $this->button('a', ['csv']),
            $this->button('b', ['xml']),
            'group' => [$this->button('c', ['xls'])],
            DropdownAction::new('more', ['x' => [$this->button('d', ['csv'])]]),
        ];

        self::assertSame(['csv', 'xml', 'xls'], $this->formats($actions));
    }

    public function testAButtonOfARoleTheUserDoesNotHaveOffersNothing(): void
    {
        $actions = [$this->button('xml', ['xml'], 'ROLE_ADMIN')];

        self::assertSame(['xml'], $this->formats($actions, ['ROLE_ADMIN']));
        // the list has an action of export, the user may not use it: not the formats of the bundle either
        self::assertSame([], $this->formats($actions, ['ROLE_CLIENT_EXPORT']));
    }

    public function testTheButtonsOfOtherRolesDoNotOpenTheirFormats(): void
    {
        $actions = [$this->button('csv', ['csv']), $this->button('xml', ['xml'], 'ROLE_ADMIN')];

        self::assertSame(['csv'], $this->formats($actions));
    }
}
