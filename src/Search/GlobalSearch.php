<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Search;

use Lle\CruditBundle\Builder\MenuBuilder;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Datasource\DatasourceParams;
use Lle\CruditBundle\Dto\Layout\LinkElement;
use Lle\CruditBundle\Provider\ConfigProvider;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\PropertyAccess\Exception\ExceptionInterface as PropertyAccessException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The global search of the layout (command palette, Ctrl K): the groups of results for a text.
 *
 * - One group per entity of `lle_crudit.search.entities`, in that order: the `autocomplete` of its CrudConfig (the
 *   search fields of its datasource), the first results only (`limit`). A result opens the page "show" of the CrudConfig
 *   and is given only to a user granted ROLE_<NAME>_SHOW on it, the resource as subject (as the page itself does).
 * - Then the group of the screens (`lle_crudit.search.screens`): the links of the menu that the user sees (MenuBuilder),
 *   whose label contains the text. Their subtitle is the sub-menu they are in.
 *
 * The entities are never "all that have an autocomplete": a project has dozens, only a few are useful here.
 *
 * @phpstan-type Result array{id: string, title: string, subtitle: ?string, url: string}
 * @phpstan-type Group array{id: string, label: string, results: list<Result>}
 */
class GlobalSearch
{
    /** Characters of a text searched: the rest is cut. */
    public const int MAX_LENGTH = 100;

    /** Screens given: a menu has no more that is useful at a glance, and they are not data. */
    public const int SCREENS_LIMIT = 5;

    /**
     * @param list<array{config: string, title: ?string, limit: int, subtitle: ?string}> $entities
     */
    public function __construct(
        private readonly ConfigProvider $configProvider,
        private readonly MenuBuilder $menuBuilder,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly RouterInterface $router,
        private readonly TranslatorInterface $translator,
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly array $entities,
        private readonly bool $screens,
        private readonly int $minLength,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /**
     * @return list<Group>
     */
    public function search(string $text): array
    {
        $text = trim(mb_substr($text, 0, self::MAX_LENGTH));
        if (mb_strlen($text) < $this->minLength) {
            return [];
        }

        $groups = [];
        foreach ($this->entities as $entity) {
            $group = $this->guarded(fn (): ?array => $this->entityGroup($entity, $text), $entity['config']);
            if ($group !== null) {
                $groups[] = $group;
            }
        }

        if ($this->screens) {
            $group = $this->guarded(fn (): ?array => $this->screensGroup($text), 'screens');
            if ($group !== null) {
                $groups[] = $group;
            }
        }

        return $groups;
    }

    /**
     * A group that fails (a datasource without search field, an entity without __toString(), a menu provider that
     * throws) is logged and left out: the other groups still answer.
     *
     * @param \Closure(): ?array<string, mixed> $build
     *
     * @return Group|null
     */
    private function guarded(\Closure $build, string $name): ?array
    {
        try {
            /** @var Group|null */
            return $build();
        } catch (\Throwable $exception) {
            $this->logger->error('Global search: the group "{group}" failed.', ['group' => $name, 'exception' => $exception]);

            return null;
        }
    }

    /**
     * @param array{config: string, title: ?string, limit: int, subtitle: ?string} $entity
     *
     * @return Group|null
     */
    private function entityGroup(array $entity, string $text): ?array
    {
        $config = $this->configProvider->get($entity['config']);
        if ($config === null) {
            // SearchEntitiesPass refuses it when the container is built: only a ConfigProvider built by hand gets here.
            throw new \LogicException(sprintf('"%s" is not a CrudConfig of the application (a service tagged crudit.config).', $entity['config']));
        }

        $role = sprintf('ROLE_%s_%s', $config->getName(), CrudConfigInterface::SHOW);
        // No result is given to a user who may not see any: no need to ask the database.
        if (!$this->authorizationChecker->isGranted($role)) {
            return null;
        }

        $items = $config->getDatasource()->autocompleteQuery(
            $text,
            $config->getDefaultAutocompleteSort() ?? $config->getDefaultSort(),
            new DatasourceParams($entity['limit'], 0, []),
        );

        $results = [];
        foreach ($items as $item) {
            if (!$this->authorizationChecker->isGranted($role, $item)) {
                continue;
            }

            $id = $this->propertyAccessor->getValue($item, 'id');
            $path = $config->getPath(CrudConfigInterface::SHOW, ['id' => $id]);
            $results[] = [
                'id' => $config->getName() . '-' . $id,
                'title' => (string) $item,
                'subtitle' => $entity['subtitle'] !== null ? $this->subtitle($item, $entity['subtitle']) : null,
                'url' => $this->router->generate($path->getRoute(), $path->getParams()),
            ];
        }

        if ($results === []) {
            return null;
        }

        return ['id' => (string) $config->getName(), 'label' => $this->label($config, $entity['title']), 'results' => $results];
    }

    private function label(CrudConfigInterface $config, ?string $title): string
    {
        if ($title !== null) {
            return $this->translator->trans($title, [], $config->getTranslationDomain());
        }

        // The title of the list, when the project translates it: else the name of the CrudConfig.
        $key = (string) $config->getTitle('list');
        $label = $this->translator->trans($key, [], $config->getTranslationDomain());

        return $label !== $key ? $label : ucfirst(strtolower((string) $config->getName()));
    }

    private function subtitle(object $item, string $propertyPath): ?string
    {
        try {
            $value = $this->propertyAccessor->getValue($item, $propertyPath);
        } catch (PropertyAccessException) {
            return null;
        }

        $subtitle = \is_scalar($value) || $value instanceof \Stringable ? trim((string) $value) : '';

        return $subtitle !== '' ? $subtitle : null;
    }

    /**
     * @return Group|null
     */
    private function screensGroup(string $text): ?array
    {
        $needle = $this->fold($text);
        $screen = $this->translator->trans('crudit.search.screen', [], 'LleCruditBundle');
        $results = [];

        foreach ($this->links($this->menuBuilder->build('menu_side'), null) as [$link, $parent]) {
            $label = $this->translator->trans((string) $link->getLibelle());
            if (!str_contains($this->fold($label), $needle)) {
                continue;
            }

            $path = $link->getPath();
            \assert($path !== null);
            $results[] = [
                'id' => 'screen-' . \count($results),
                'title' => $label,
                'subtitle' => $parent ?? $screen,
                'url' => $this->router->generate($path->getRoute(), $path->getParams()),
            ];
            if (\count($results) === self::SCREENS_LIMIT) {
                break;
            }
        }

        if ($results === []) {
            return null;
        }

        return [
            'id' => 'screens',
            'label' => $this->translator->trans('crudit.search.screens', [], 'LleCruditBundle'),
            'results' => $results,
        ];
    }

    /**
     * The links of the menu that go to a page of the application, with the label of the sub-menu they are in.
     *
     * @param iterable<object> $elements
     *
     * @return \Generator<int, array{0: LinkElement&object, 1: ?string}>
     */
    private function links(iterable $elements, ?string $parent): \Generator
    {
        foreach ($elements as $element) {
            if (!$element instanceof LinkElement) {
                continue;
            }

            if ($element->getPath() !== null) {
                yield [$element, $parent];
            }
            yield from $this->links($element->getChildren(), $this->translator->trans((string) $element->getLibelle()));
        }
    }

    /**
     * "Café" and "cafe" are the same word: without accents, in lower case.
     */
    private function fold(string $text): string
    {
        static $transliterator = false;
        $transliterator = $transliterator === false ? \Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC; Lower') : $transliterator;
        $folded = $transliterator;

        return $folded !== null ? (string) $folded->transliterate($text) : mb_strtolower($text);
    }
}
