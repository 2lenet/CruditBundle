<?php

namespace Lle\CruditBundle\Service\EasyAdminConverter;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Maker\MakeCrudit;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Generator;

class Converter
{
    public const LIST_ACTIONS = ['new', 'export'];
    public const ITEM_ACTIONS = ['show', 'edit', 'delete'];
    public const SHOW_ACTIONS = ['edit', 'delete'];
    protected array $logs = [];
    protected Generator $generator;
    protected MakeCrudit $cruditMaker;
    protected DoctrineHelper $doctrineHelper;

    public function __construct(
        Generator $generator,
        MakeCrudit $cruditMaker,
        DoctrineHelper $doctrineHelper,
    ) {
        $this->generator = $generator;
        $this->cruditMaker = $cruditMaker;
        $this->doctrineHelper = $doctrineHelper;
    }

    public function convert(array $config): iterable
    {
        if (isset($config['entities'])) {
            foreach ($config['entities'] as $short => $entityConfig) {
                if (isset($entityConfig['filter'])) {
                    yield from $this->makeFilterSet($entityConfig, $short);
                }
                yield from $this->makeDatasource($entityConfig, $short);
                yield from $this->makeCrudConfig($entityConfig, $short);
                yield from $this->makeController($entityConfig, $short);
                yield from $this->makeFormType($entityConfig, $short);
            }
        }

        yield from $this->makeMenu($config);

        yield 'success' => 'Conversion finished ! Please note the warnings above and fix them.';
        yield 'warning' => 'Note that the converter is not perfect. Some things were ignored, some things were modified. You need to check all pages.';
    }

    protected function makeFilterSet(array $entityConfig, string $prefixFilename): iterable
    {
        $entityClass = $entityConfig['class'];
        $shortEntity = $this->getShortEntityName($entityClass);

        $filters = [];
        /** @var ClassMetadataInfo $metadata */
        $metadata = $this->doctrineHelper->getMetadata($entityClass);
        foreach ($entityConfig['filter']['fields'] as $filter) {
            $filters[] = $this->cruditMaker->getFilterType($metadata, $filter['property']);
        }

        $uses = [];
        foreach ($filters as $filter) {
            array_push($uses, ...$filter['uses']);
        }
        $uses = array_unique($uses);
        sort($uses);

        $filtersetClassNameDetails = $this->generator->createClassNameDetails(
            $prefixFilename,
            'Crudit\Datasource\Filterset\\',
            'FilterSet'
        );

        $this->generator->generateClass(
            $filtersetClassNameDetails->getFullName(),
            $this->cruditMaker->getSkeletonTemplate('filterset/EntityFilterset.php'),
            [
                'namespace' => 'App',
                'prefixFilename' => $prefixFilename,
                'fullEntityClass' => $entityClass,
                'filters' => $filters,
                'strictType' => true,
                'uses' => $uses,
            ]
        );
        $this->generator->writeChanges();

        yield;
    }

    protected function makeDatasource(array $entityConfig, string $prefixFilename): iterable
    {
        $entityClass = $entityConfig['class'];
        $shortEntity = $this->getShortEntityName($entityClass);

        $datasourceClassNameDetails = $this->generator->createClassNameDetails(
            $prefixFilename,
            'Crudit\\Datasource\\',
            'Datasource'
        );
        $this->generator->generateClass(
            $datasourceClassNameDetails->getFullName(),
            $this->cruditMaker->getSkeletonTemplate('datasource/DoctrineDatasource.php'),
            [
                'namespace' => 'App',
                'prefixFilename' => $prefixFilename,
                'entityClass' => $shortEntity,
                'hasFilterset' => isset($entityConfig['filter']),
                'fullEntityClass' => $entityClass,
                'strictType' => true,
            ]
        );
        $this->generator->writeChanges();

        yield;
    }

    protected function makeCrudConfig(array $entityConfig, string $prefixFilename): iterable
    {
        $entityClass = $entityConfig['class'];
        $shortEntity = $this->getShortEntityName($entityClass);
        $fields = [];
        $cruds = [];

        foreach ($entityConfig['list']['fields'] ?? [] as $property) {
            if (!isset($property['property'])) {
                yield 'warning' => 'Property ' . http_build_query($property, '', ' ') . ' ignored';
                continue;
            }

            $sortable = isset($property['sortable']) ? $property['sortable'] : true;

            // the duplicate field is on purpose
            $fields[] = Field::new($property['property']);
            $cruds['CrudConfigInterface::INDEX'][] = Field::new($property['property'])
                ->setSortable($sortable);
        }

        $group = false;
        $groupName = null;
        $groupId = 0;
        foreach ($entityConfig['show']['fields'] ?? [] as $property) {
            if ((!isset($property['property']) && !(isset($property['type']) && $property['type'] === 'group')) || (isset($property['type']) && $property['type'] !== 'group')) {
                yield 'warning' => 'Property ' . http_build_query($property, '', ' ') . ' ignored';
                continue;
            }

            // the duplicate field is on purpose
            if (!isset($property['type'])) {
                $fields[] = Field::new($property['property']);
            }

            if (isset($property['type']) && ($property['type'] === 'sublist' || $property['type'] === 'tab')) {
                continue;
            }

            // group management
            if (isset($property['type']) && $property['type'] === 'group') {
                $group = true;

                if (!array_key_exists('CrudConfigInterface::SHOW', $cruds)) {
                    $cruds['CrudConfigInterface::SHOW'] = [];
                }

                if (isset($property['label'])) {
                    $groupName = $property['label'];
                    $cruds['CrudConfigInterface::SHOW'][$groupName] = [];
                } else {
                    $groupName = ++$groupId;
                    $cruds['CrudConfigInterface::SHOW'][$groupName] = [];
                }
            } else {
                if ($group) {
                    $cruds['CrudConfigInterface::SHOW'][$groupName][] = Field::new($property['property']);
                } else {
                    $cruds['CrudConfigInterface::SHOW'][$groupName][] = Field::new($property['property']);
                }
            }
        }

        foreach ($entityConfig['export']['fields'] ?? [] as $property) {
            if (!isset($property['property'])) {
                yield 'warning' => 'Property ' . http_build_query($property, '', ' ') . ' ignored';
                continue;
            }

            // the duplicate field is on purpose
            $fields[] = Field::new($property['property']);
            $cruds['CrudConfigInterface::EXPORT'][] = Field::new($property['property']);
        }

        $forms = [];
        if (!isset($entityConfig['form'])) {
            if (isset($entityConfig['edit'])) {
                $forms['CrudConfigInterface::EDIT'] = 'Edit';
            }
            if (isset($entityConfig['new'])) {
                $forms['CrudConfigInterface::NEW'] = 'New';
            }
        }

        $tabs = [];
        if (isset($entityConfig['show']['fields'])) {
            $tabs = $this->getTabs($entityConfig);

            foreach ($tabs['ignoredTabs'] as $ignoredTab) {
                yield 'warning' => 'Tab ' . http_build_query($ignoredTab, '', ' ') . ' ignored';
            }
        }

        $sort = [];
        if (isset($entityConfig['list']['sort'])) {
            $sort['property'] = $entityConfig['list']['sort'][0];
            $sort['order'] = $entityConfig['list']['sort'][1];
        }

        $disabledActions = [];
        if (isset($entityConfig['disabled_actions']) && $entityConfig['disabled_actions']) {
            $disabledActions['list'] = array_intersect($entityConfig['disabled_actions'], self::LIST_ACTIONS);
            $disabledActions['item'] = array_intersect($entityConfig['disabled_actions'], self::ITEM_ACTIONS);
            $disabledActions['show'] = array_intersect($entityConfig['disabled_actions'], self::SHOW_ACTIONS);
        }

        $listAndItemActions = [];
        if (isset($entityConfig['list']['actions'])) {
            $listAndItemActions = $this->getListAndItemActions($entityConfig);

            foreach ($listAndItemActions['ignoredActions'] as $ignoredAction) {
                yield 'warning' => 'Action ' . http_build_query($ignoredAction, '', ' ') . ' ignored';
            }
        }

        $showActions = [];
        if (isset($entityConfig['show']['actions'])) {
            $showActions = $this->getShowActions($entityConfig);

            foreach ($showActions['ignoredActions'] as $ignoredAction) {
                yield 'warning' => 'Action ' . http_build_query($ignoredAction, '', ' ') . ' ignored';
            }
        }

        $configuratorClassNameDetails = $this->generator->createClassNameDetails(
            $prefixFilename,
            'Crudit\\Config\\',
            'CrudConfig'
        );

        $this->generator->generateClass(
            $configuratorClassNameDetails->getFullName(),
            $this->cruditMaker->getSkeletonTemplate('config/CrudAutoConfig.php'),
            [
                'namespace' => 'App',
                // array_unique with flag SORT_REGULAR will compare object properties.
                'fields' => array_unique($fields, SORT_REGULAR),
                'cruds' => $cruds,
                'prefixFilename' => $prefixFilename,
                'fullEntityClass' => $entityClass,
                'strictType' => true,
                'forms' => $forms,
                'tabs' => array_key_exists('tabs', $tabs) ? $tabs['tabs'] : [],
                'sort' => $sort,
                'controllerRoute' => 'crudit_' . $shortEntity,
                'disabledActions' => $disabledActions,
                'listActions' => array_key_exists(
                    'listActions',
                    $listAndItemActions
                ) ? $listAndItemActions['listActions'] : [],
                'itemActions' => array_key_exists(
                    'itemActions',
                    $listAndItemActions
                ) ? $listAndItemActions['itemActions'] : [],
                'showActions' => array_key_exists('showActions', $showActions) ? $showActions['showActions'] : [],
            ]
        );

        $this->generator->writeChanges();

        yield;
    }

    protected function makeController(array $entityConfig, string $prefixFilename): iterable
    {
        $entityClass = $entityConfig['class'];
        $shortEntity = $this->getShortEntityName($entityClass);

        $controllerClassNameDetails = $this->generator->createClassNameDetails(
            $prefixFilename,
            'Controller\\Crudit\\',
            'Controller'
        );
        $this->generator->generateClass(
            $controllerClassNameDetails->getFullName(),
            $this->cruditMaker->getSkeletonTemplate('controller/CrudController.php'),
            [
                'namespace' => 'App',
                'fullEntityClass' => $entityClass,
                'prefixFilename' => $prefixFilename,
                'strictType' => true,
            ]
        );
        $this->generator->writeChanges();

        yield;
    }

    protected function makeFormType(array $entityConfig, string $prefixFilename): iterable
    {
        if (isset($entityConfig['form']['fields'])) {
            $this->addFormType($entityConfig['form']['fields'], '', $prefixFilename);
        } else {
            if (isset($entityConfig['edit']['fields'])) {
                $this->addFormType($entityConfig['edit']['fields'], 'Edit', $prefixFilename);
            }
            if (isset($entityConfig['new']['fields'])) {
                $this->addFormType($entityConfig['new']['fields'], 'New', $prefixFilename);
            }
        }

        $this->generator->writeChanges();

        yield;
    }

    public function makeMenu(array $config): iterable
    {
        if (!isset($config['design']['menu'])) {
            yield;
        }

        $items = [];

        foreach ($config['design']['menu'] as $menu) {
            $parent = $this->getMenuItem($config, $menu);
            if (!$parent) {
                continue;
            }
            $items[] = $parent;

            if (isset($menu['children'])) {
                foreach ($menu['children'] as $child) {
                    $item = $this->getMenuItem($config, $child);
                    if (!$item) {
                        continue;
                    }
                    $item['parent'] = $parent['label'];
                    $items[] = $item;
                }
            }
        }

        $className = 'MenuProvider';
        $classDetails = $this->generator->createClassNameDetails(
            'MenuProvider',
            'Crudit\\CrudMenu',
        );
        $this->generator->generateClass(
            $classDetails->getFullName(),
            $this->cruditMaker->getSkeletonTemplate('menu/MenuProvider.php'),
            [
                'namespace' => 'App',
                'className' => $className,
                'items' => $items,
            ]
        );
        $this->generator->writeChanges();

        yield;
    }

    protected function getMenuItem(array $config, mixed $menu): ?array
    {
        $item = null;
        if (is_string($menu)) {
            // entity
            $entity = $config['entities'][$menu]['class'];

            $item = [
                'label' => 'menu.' . strtolower($menu),
                'route' => 'app_crudit_' . strtolower($this->getShortEntityName($entity)) . '_index',
            ];
        } elseif (is_array($menu)) {
            if (!isset($menu['entity']) && !isset($menu['url']) && !isset($menu['route']) && !isset($menu['children'])) {
                $item = ['type' => 'separator'];
                if (isset($menu['role'])) {
                    $item['role'] = $menu['role'];
                }
            } else {
                $item = [
                    'label' => $menu['label'] ?? 'menu.' . strtolower($menu['entity']),
                ];

                if (isset($menu['icon'])) {
                    $item['icon'] = $menu['icon'];
                }

                if (isset($menu['entity'])) {
                    $entity = $config['entities'][$menu['entity']]['class'];
                    $item['route'] = 'app_crudit_' . strtolower($this->getShortEntityName($entity)) . '_index';
                }

                if (isset($menu['role'])) {
                    $item['role'] = $menu['role'];
                }

                if (isset($menu['route'])) {
                    $item['route'] = $menu['route'];
                }

                if (isset($menu['url'])) {
                    $item['url'] = $menu['url'];
                }
            }
        }

        return $item;
    }

    protected function addFormType(array $properties, string $prefix, string $prefixFilename): void
    {
        $fields = [];
        foreach ($properties as $property) {
            if (array_key_exists('property', $property)) {
                $fields[] = Field::new($property['property']);
            }
        }

        $formTypeClassNameDetails = $this->generator->createClassNameDetails(
            $prefix . $prefixFilename,
            'Form\\',
            'Type'
        );
        $this->generator->generateClass(
            $formTypeClassNameDetails->getFullName(),
            $this->cruditMaker->getSkeletonTemplate('form/EntityCruditType.php'),
            [
                'namespace' => 'App',
                'prefixFilename' => $prefix . $prefixFilename,
                'fields' => $fields,
                'strictType' => true,
            ]
        );
    }

    protected function getTabs(array $entityConfig): array
    {
        $tabs = [];
        $ignoredTabs = [];

        foreach ($entityConfig['show']['fields'] as $field) {
            if (is_array($field) && isset($field['type'])) {
                if ($field['type'] === 'sublist') {
                    /** @var ClassMetadataInfo $metadata */
                    $metadata = $this->doctrineHelper->getMetadata($entityConfig['class']);
                    // 'No mapping found for field ...' => your sublist property does not exist

                    $association = null;
                    if (!isset($field['property'])) {
                        foreach ($metadata->getAssociationMappings() as $mapping) {
                            if ($this->getShortEntityName($mapping['targetEntity']) === $field['entity']) {
                                $association = $mapping;
                                break;
                            }
                        }
                    } elseif (isset($metadata->getAssociationMappings()[$field['property']])) {
                        $association = $metadata->getAssociationMapping($field['property']);
                    } else {
                        $ignoredTabs[] = $field;
                    }

                    if ($association) {
                        $mappedBy = $association['mappedBy'];

                        $tabs['sublist'][] = [
                            'type' => 'sublist',
                            'label' => $field['label'] ?? 'tab.' . strtolower($field['property']),
                            'property' => $mappedBy,
                            'linkedEntity' => $this->getShortEntityName($association['targetEntity']),
                        ];
                    }
                } elseif ($field['type'] === 'tab' && isset($field['action']) && $field['action'] === 'historyAction') {
                    $tabs['history'][] = [
                        'type' => 'history',
                        'label' => $field['label'] ?? 'tab.history',
                    ];
                }
            }
        }

        return ['tabs' => $tabs, 'ignoredTabs' => $ignoredTabs];
    }

    protected function getShortEntityName(string $class): string
    {
        return basename(str_replace('\\', '/', $class));
    }

    protected function getListAndItemActions(array $entityConfig): array
    {
        $listActions = [];
        $itemActions = [];
        $ignoredActions = [];

        foreach ($entityConfig['list']['actions'] as $action) {
            if (
                is_array($action) &&
                isset($action['global']) &&
                $action['global'] === 'true' &&
                isset($action['type']) &&
                $action['type'] === 'route'
            ) {
                $listActions[] = $this->getPropertyAction($action);
            } elseif (
                is_array($action) &&
                (!isset($action['global']) || ($action['global'] === 'false')) &&
                isset($action['type']) &&
                $action['type'] === 'route'
            ) {
                $itemActions[] = $this->getPropertyAction($action);
            } else {
                $ignoredActions[] = $action;
            }
        }

        return ['listActions' => $listActions, 'itemActions' => $itemActions, 'ignoredActions' => $ignoredActions];
    }

    protected function getShowActions(array $entityConfig): array
    {
        $showActions = [];
        $ignoredActions = [];

        foreach ($entityConfig['show']['actions'] as $action) {
            if (is_array($action) && isset($action['type']) && $action['type'] === 'route') {
                $showActions[] = $this->getPropertyAction($action);
            } else {
                $ignoredActions[] = $action;
            }
        }

        return ['showActions' => $showActions, 'ignoredActions' => $ignoredActions];
    }

    protected function getPropertyAction(array $action): array
    {
        return [
            'label' => isset($action['label']) ? str_replace('label', 'action', $action['label']) : null,
            'path' => isset($action['name']) ? $action['name'] : null,
            'icon' => isset($action['icon']) ? $action['icon'] : null,
            'cssClass' => isset($action['css_class']) ? $action['css_class'] : null,
            'target' => isset($action['target']) ? $action['target'] : null,
        ];
    }
}
