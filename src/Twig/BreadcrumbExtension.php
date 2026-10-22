<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Twig;

use Lle\CruditBundle\Builder\BreadcrumbBuilder;
use Lle\CruditBundle\Routing\HomeUrl;
use Twig\Attribute\AsTwigFunction;

/**
 * The breadcrumb of the page (BreadcrumbBuilder), for the Breadcrumb and the PageHeader of CruditUiBundle:
 * - crudit_breadcrumb(current): the items of the current page after the entry of the menu (a page of the project);
 * - crudit_crud_breadcrumb(record): a page of a CRUD, the record and its action from the route;
 * - crudit_home_url(): the root of the application (the link of the logo).
 */
class BreadcrumbExtension
{
    public function __construct(
        protected BreadcrumbBuilder $breadcrumbBuilder,
        protected HomeUrl $homeUrl,
    ) {
    }

    /**
     * @param list<array{label: string, url?: string}> $current
     *
     * @return list<array{label: string, url?: string}>
     */
    #[AsTwigFunction('crudit_breadcrumb')]
    public function breadcrumb(array $current = []): array
    {
        return $this->breadcrumbBuilder->build($current);
    }

    /**
     * @return list<array{label: string, url?: string}>
     */
    #[AsTwigFunction('crudit_crud_breadcrumb')]
    public function crudBreadcrumb(string $record): array
    {
        return $this->breadcrumbBuilder->buildForCrud($record);
    }

    #[AsTwigFunction('crudit_home_url')]
    public function homeUrl(): string
    {
        return $this->homeUrl->url();
    }
}
