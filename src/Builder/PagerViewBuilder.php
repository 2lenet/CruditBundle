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
    public function build(BrickView $view): ?array
    {
        $config = $view->getConfig();
        $params = $config['datasource_params'] ?? null;
        if (!$params instanceof DatasourceParams || !($config['name'] ?? null)) {
            return null;
        }

        $name = strtolower((string) $config['name']);
        // The current request, as the filters and the sort links of the table: a list rendered in a sub-request keeps its parameters
        $request = $this->requestStack->getCurrentRequest();
        // The only rule of Crudit: the sublist does not let the user change the rows per page (canModifyNbEntityPerPage),
        // nor does a limit of one row. The Pager adds the limit in use to the choices, sorts them and drops the doubles.
        $choices = ($config['canModifyNbEntityPerPage'] ?? true) !== false && $params->getLimit() > 1
            ? ($config['choices_nb_items'] ?? [])
            : [];

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
        ];
    }

    /**
     * The plural name of the elements, for the summary ("1 à 30 sur 131 clients"): the translation of
     * `crud.pager.label.<name>`, else the title of the list (`crud.title.list.<name>`) when it is translated, else the
     * name of the list. The first letter is put in lowercase, except in an acronym ("PDF reçus").
     *
     * @param array<string, mixed> $config
     */
    private function label(array $config): string
    {
        $domain = $config['translation_domain'] ?? null;
        $name = (string) $config['name'];
        $label = $name;
        foreach (['crud.pager.label.' . strtolower($name), $config['title'] ?? null] as $key) {
            if (!$key) {
                continue;
            }
            $translated = $this->translator->trans((string) $key, [], $domain);
            // an untranslated key comes back as it is; a title a project wrote as a text ("Clients") is not a key
            if ($translated !== $key || !preg_match('/^[\w-]+(\.[\w-]+)+$/', (string) $key)) {
                $label = $translated;
                break;
            }
        }

        $second = mb_substr($label, 1, 1);

        return $second !== '' && $second === mb_strtoupper($second) && $second !== mb_strtolower($second)
            ? $label
            : mb_strtolower(mb_substr($label, 0, 1)) . mb_substr($label, 1);
    }
}
