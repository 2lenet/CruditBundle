<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Twig;

use Lle\CruditBundle\Builder\ExportViewBuilder;
use Twig\Attribute\AsTwigFunction;

/**
 * `crudit_export(config, domain)`: the props of the ExportDialog of CruditUiBundle (see ExportViewBuilder).
 */
class ExportExtension
{
    public function __construct(private ExportViewBuilder $builder)
    {
    }

    /**
     * @param array<string, mixed>|null $config
     *
     * @return array<string, mixed>
     */
    #[AsTwigFunction('crudit_export')]
    public function export(?array $config, ?string $domain = null): array
    {
        return $this->builder->build($config ?? [], $domain);
    }
}
