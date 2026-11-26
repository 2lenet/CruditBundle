<?php

namespace Lle\CruditBundle\Exporter;

use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\FieldView;
use Lle\CruditBundle\Dto\ResourceView;
use Lle\CruditBundle\Exception\ExporterException;
use Lle\CruditBundle\Exporter\Pdf\PdfWriter;
use Lle\CruditBundle\Field\BooleanField;
use Lle\CruditBundle\Field\CurrencyField;
use Lle\CruditBundle\Field\IntegerField;
use Lle\CruditBundle\Field\NumberField;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Contracts\Translation\TranslatorInterface;

class PdfExporter extends AbstractExporter
{
    private const ALIGN_LEFT = 'left';
    private const ALIGN_RIGHT = 'right';
    private const ALIGN_CENTER = 'center';
    private const DEFAULT_DECIMALS = 2;
    private const NUMERIC_TYPES = [
        'bigint', 'smallint', 'float', 'integer', 'decimal', NumberField::class, IntegerField::class,
    ];

    public function __construct(
        protected TranslatorInterface $translator,
    ) {
    }

    public function getSupportedFormat(): string
    {
        return Exporter::PDF;
    }

    public function getContentType(): string
    {
        return 'application/pdf';
    }

    public function getFileExtension(): string
    {
        return '.pdf';
    }

    public function export(iterable $resources, ExportParams $params, array $totals = []): string
    {
        $pdfParams = $params->getPdfParams();
        $headers = [];
        $alignments = [];
        $rows = [];

        /** @var ResourceView $resource */
        foreach ($resources as $resource) {
            if ($headers === []) {
                $headers = $this->getHeaders($resource->getFields());
            }

            $row = [];
            /** @var FieldView $field */
            foreach ($resource->getFields() as $column => $field) {
                $row[] = $this->formatField($field, $this->getValue($field), $pdfParams);
                $alignments[$column] ??= $this->getAlignment($field);
            }
            $rows[] = $row;
        }

        $path = tempnam(sys_get_temp_dir(), Exporter::PDF);
        if ($path === false) {
            throw new ExporterException('Unknown PDF exporter error');
        }

        file_put_contents($path, $this->createWriter($pdfParams)->write(
            $params->getIncludeHeaders() ? $headers : [],
            $rows,
            $this->getTotalsRow($headers, $totals, $pdfParams),
            $alignments
        ));

        return $path;
    }

    protected function createWriter(array $pdfParams): PdfWriter
    {
        return new PdfWriter($pdfParams);
    }

    protected function getHeaders(array $fields): array
    {
        $result = [];

        /** @var FieldView $field */
        foreach ($fields as $field) {
            $result[] = $this->translator->trans($field->getField()->getLabel());
        }

        return $result;
    }

    protected function getAlignment(FieldView $field): string
    {
        if ($this->isCurrency($field->getField()) || $this->isNumeric($field)) {
            return self::ALIGN_RIGHT;
        }

        return $this->isBoolean($field) ? self::ALIGN_CENTER : self::ALIGN_LEFT;
    }

    public function getDecimals(Field $field): int
    {
        if ($field->getType() === IntegerField::class) {
            return 0;
        }

        return $field->getOptions()['decimals'] ?? self::DEFAULT_DECIMALS;
    }

    protected function formatField(FieldView $field, string $value, array $pdfParams): string
    {
        if ($this->isBoolean($field)) {
            return $this->formatBoolean($field);
        }

        if ($value === '') {
            return '';
        }

        if ($this->isCurrency($field->getField())) {
            return $this->formatCurrency($field);
        }

        if ($this->isNumeric($field) && is_numeric($value)) {
            return number_format(
                (float)$value,
                $this->getDecimals($field->getField()),
                $pdfParams['decimal_separator'],
                $pdfParams['thousands_separator']
            );
        }

        return $value;
    }

    protected function formatBoolean(FieldView $field): string
    {
        $rawValue = $field->getRawValue();
        if ($rawValue === null || $rawValue === '') {
            return '';
        }

        return $this->translator->trans(
            $rawValue ? 'crudit.boolean.yes' : 'crudit.boolean.no',
            [],
            'LleCruditBundle'
        );
    }

    protected function formatCurrency(FieldView $field): string
    {
        $options = $field->getOptions();
        $currency = '';
        if (($options['property'] ?? null) && $field->getResource()) {
            $currency = $field->getResource();
            $propertyAccessor = PropertyAccess::createPropertyAccessor();
            foreach (explode('.', $options['property']) as $property) {
                $currency = $propertyAccessor->getValue($currency, $property);
            }
        } elseif ($options['currency'] ?? null) {
            $currency = $options['currency'];
        }

        $amount = $field->getField()->getType() === CurrencyField::class
            ? $field->getRawValue()
            : (float)trim($field->getValue() ?? '');

        $formatter = \NumberFormatter::create($options['locale'], \NumberFormatter::CURRENCY);

        return (string)$formatter->formatCurrency((float)$amount, (string)$currency);
    }

    /**
     * @return string[]|null
     */
    protected function getTotalsRow(array $headers, array $totals, array $pdfParams): ?array
    {
        if (!$totals) {
            return null;
        }

        $row = array_fill(0, count($headers), '');
        foreach ($headers as $column => $header) {
            foreach ($totals as $total) {
                if ($header === $this->translator->trans($total['field']->getField()->getLabel())) {
                    $row[$column] = $this->formatField($total['field'], (string)$total['total'], $pdfParams);
                }
            }
        }

        return $row;
    }

    private function isCurrency(Field $field): bool
    {
        return $field->getType() === CurrencyField::class || $field->getType() === 'currency';
    }

    private function isBoolean(FieldView $field): bool
    {
        if ($field->getField()->getTemplate() && $field->getField()->getType() === 'boolean') {
            return false;
        }

        return in_array($field->getField()->getType(), ['boolean', BooleanField::class], true);
    }

    private function isNumeric(FieldView $field): bool
    {
        return in_array($field->getField()->getType(), self::NUMERIC_TYPES, true);
    }
}
