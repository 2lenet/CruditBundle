<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Twig;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Configuration of the layout (`lle_crudit.layout`): logo, favicon, version and links of the footer. Read by the
 * fragments of the layout (sidebar/_logo, footer/), which the blocks brand, favicon and footer replace.
 *
 * - `version`: the one of the configuration, else the Twig global app_version of the project, else null (an empty
 *   value also falls back);
 * - `release_date`: a date (Y-m-d) or null. An invalid date is logged and not shown: the footer is on every page, it
 *   must not break them.
 */
class LayoutExtension extends AbstractExtension
{
    /** @var array<string, mixed>|null */
    private ?array $resolved = null;

    /**
     * @param array{
     *     logo: ?string,
     *     logo_dark: ?string,
     *     logo_small: ?string,
     *     favicon: ?string,
     *     version: ?string,
     *     release_date: ?string,
     *     footer_links: list<array{label: string, route: ?string, route_params: array<string, mixed>, url: ?string}>,
     * } $layout
     */
    public function __construct(
        protected array $layout,
        protected LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('crudit_layout', [$this, 'layout'], ['needs_environment' => true])];
    }

    /**
     * @return array<string, mixed>
     */
    public function layout(Environment $twig): array
    {
        if ($this->resolved === null) {
            $globals = $twig->getGlobals();
            $version = $this->layout['version'] ?: ($globals['app_version'] ?? null);

            $this->resolved = [
                ...$this->layout,
                'version' => \is_scalar($version) && (string) $version !== '' ? (string) $version : null,
                'release_date' => $this->releaseDate($this->layout['release_date']),
            ];
        }

        return $this->resolved;
    }

    private function releaseDate(?string $value): ?\DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if ($date === false || $date->format('Y-m-d') !== $value) {
            $this->logger->warning('lle_crudit.layout.release_date "{value}" is not a date (Y-m-d): not shown.', [
                'value' => $value,
            ]);

            return null;
        }

        return $date;
    }
}
