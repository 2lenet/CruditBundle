<?php

declare(strict_types=1);

namespace phpunit\Unit\Exporter;

use Lle\CruditBundle\Contracts\ExporterInterface;
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
 * The formats that a page offers for the export (Exporter::resolveActionsFormats(), what the controller accepts): those of the buttons of the list that the user may use.
 */
class ExportActionsFormatsTest extends TestCase
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
     * @param array<int|string, mixed> $actions the list actions of the config
     * @param list<string> $roles the roles the user has
     *
     * @return list<string>
     */
    private function formats(array $actions, array $roles = []): array
    {
        return $this->exporter()->resolveActionsFormats($actions, Path::new('app_client_EXPORT')->getRoute(), fn (string $role): bool => in_array($role, $roles, true));
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
