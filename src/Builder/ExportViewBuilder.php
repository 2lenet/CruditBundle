<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Builder;

use Lle\CruditBundle\Contracts\DescribedExporterInterface;
use Lle\CruditBundle\Exporter\Exporter;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The props of the ExportDialog of CruditUiBundle for the action "Export" of a list (lot 3.1.g): the formats with their
 * name, sentence and icon. The formats are those of `setConfig(['export' => [...]])` of the action when it has some (the
 * choice of the project), else all the exporters. The name of a format of the bundle is translated
 * (`crudit.export.format.<format>`); a format of a project describes itself (DescribedExporterInterface), else it is the
 * translation `export.<format>` of the domain of the list (1.x), else its code in capitals.
 */
class ExportViewBuilder
{
    private const array ICONS = ['xls' => 'file-excel', 'csv' => 'file-csv', 'pdf' => 'file-pdf'];

    public function __construct(
        private Exporter $exporter,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param array<string, mixed> $config the config of the action
     *
     * @return array{formats: list<array{value: string, label: string, description: ?string, icon: string}>, title: string, description: string}
     */
    public function build(array $config, ?string $domain): array
    {
        $configured = $config['export'] ?? null;
        $codes = is_iterable($configured) ? array_values(array_unique([...$configured])) : $this->exporter->getFormats();

        $formats = [];
        foreach ($codes as $code) {
            $formats[] = $this->format((string) $code, $domain);
        }

        return [
            'formats' => $formats,
            'title' => $this->translator->trans('crudit.export.title', [], 'LleCruditBundle'),
            'description' => $this->translator->trans('crudit.export.description', [], 'LleCruditBundle'),
        ];
    }

    /**
     * @return array{value: string, label: string, description: ?string, icon: string}
     */
    private function format(string $code, ?string $domain): array
    {
        $exporter = $this->exporter->findExporter($code);
        if ($exporter instanceof DescribedExporterInterface) {
            return [
                'value' => $code,
                'label' => $exporter->getLabel(),
                'description' => $exporter->getDescription(),
                'icon' => $exporter->getIcon() ?? 'file',
            ];
        }

        $key = 'crudit.export.format.' . $code;
        $label = $this->translator->trans($key . '.label', [], 'LleCruditBundle');
        if ($label !== $key . '.label') {
            return [
                'value' => $code,
                'label' => $label,
                'description' => $this->translator->trans($key . '.description', [], 'LleCruditBundle'),
                'icon' => self::ICONS[$code] ?? 'file',
            ];
        }

        // 1.x: `export.<format>` in the domain of the list
        $legacy = $this->translator->trans('export.' . $code, [], $domain);

        return [
            'value' => $code,
            'label' => $legacy !== 'export.' . $code ? $legacy : strtoupper($code),
            'description' => null,
            'icon' => self::ICONS[$code] ?? 'file',
        ];
    }
}
