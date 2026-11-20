<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Builder;

use Lle\CruditBundle\Datasource\DatasourceParams;
use Lle\CruditBundle\Dto\BrickView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The props of the Pager of CruditUiBundle for the view of a list (lot 3.1.d): the page and the rows per page from
 * `DatasourceParams`, the addresses from the request. The parameters stay those of the 1.x: `<list>_offset` (the number
 * of the first row) and `<list>_limit`, in lowercase.
 */
class PagerViewBuilder
{
    public function __construct(
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @return array<string, mixed>|null null without datasource parameters
     */
    public function build(BrickView $view, ?string $anchor = null): ?array
    {
        $config = $view->getConfig();
        $params = $config['datasource_params'] ?? null;
        if (!$params instanceof DatasourceParams) {
            return null;
        }

        $name = strtolower((string) $config['name']);
        $request = $this->requestStack->getMainRequest();
        $choices = [];
        // The sublist does not let the user change the rows per page (canModifyNbEntityPerPage), nor does a single choice
        if (($config['canModifyNbEntityPerPage'] ?? true) !== false && $params->getLimit() > 1) {
            $choices = array_values(array_unique(array_map('intval', $config['choices_nb_items'] ?? [])));
            sort($choices);
            if (!in_array($params->getLimit(), $choices, true)) {
                $choices[] = $params->getLimit();
                sort($choices);
            }
            if (count($choices) < 2) {
                $choices = [];
            }
        }

        return [
            'id' => $view->getId() . '_pager',
            'label' => $this->label($config),
            'total' => $params->getCount(),
            'page' => $params->getCurrentPage(),
            'perPage' => $params->getLimit(),
            'perPageChoices' => $choices,
            'path' => $request?->getBaseUrl() . $request?->getPathInfo(),
            'query' => $request?->query->all() ?? [],
            'pageParam' => $name . '_offset',
            'mode' => 'offset',
            'limitParam' => $name . '_limit',
            'anchor' => $anchor,
        ];
    }

    /**
     * The plural name of the elements: the title of the list, translated, in lowercase ("1 à 30 sur 131 interventions").
     * Without title, the name of the list.
     *
     * @param array<string, mixed> $config
     */
    private function label(array $config): string
    {
        $title = $config['title'] ?? null;
        $label = $title
            ? $this->translator->trans((string) $title, [], $config['translation_domain'] ?? null)
            : (string) $config['name'];

        return mb_strtolower(mb_substr($label, 0, 1)) . mb_substr($label, 1);
    }
}
