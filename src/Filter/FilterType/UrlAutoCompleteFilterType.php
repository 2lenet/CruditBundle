<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Symfony\Component\Routing\RouterInterface;

/**
 * StringFilterType
 */
class UrlAutoCompleteFilterType extends AbstractFilterType
{

    protected $url;
    protected $value_filter;
    protected $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function init($columnName, $label = null, $alias = 'entity')
    {
        parent::init($columnName, $label, $alias);
        $this->data_keys = ['comparator', 'value', 'value_label'];
    }

    public function configure(array $config = [])
    {
        parent::configure($config);
        $this->data_keys = ['comparator', 'value', 'value_label'];
        $this->value_filter = $config['value_filter'] ?? null;
        $this->url = $config['url'] ?? null;
        $this->path = $config['path'] ?? null;
        $this->entity = $config['entity'] ?? null;
        if ($this->entity) {
            $path = $this->path;
            $path['route'] = 'easyadmin';
            $path['params'] = ['action' => 'autocomplete', 'entity' => $config['entity']];
            $this->url = $this->router->generate($path['route'], $path['params']);
        } elseif ($this->path) {
            $path = $this->path;
            $path['params']['action'] = $path['params']['action'] ?? 'autocomplete';
            $path['route'] = $path['route'] ?? 'easyadmin';
            $this->url = $this->router->generate($path['route'], $path['params']);
        }
    }

    /**
     * @param array  $data     The data
     * @param string $uniqueId The unique identifier
     */
    public function apply($queryBuilder)
    {
        if (isset($this->data['value']) && $this->data['value']) {
            $value = $this->data['value'];
            $queryBuilder->andWhere($this->alias . $this->columnName . '= :var_' . $this->uniqueId);
            $queryBuilder->setParameter('var_' . $this->uniqueId, $value);
        }
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getValueFilter()
    {
        return $this->value_filter;
    }

    public function getStateTemplate()
    {
        return '@LleEasyAdminPlus/filter/state/url_auto_complete_filter.html.twig';
    }

    public function getTemplate()
    {
        return '@LleEasyAdminPlus/filter/type/url_auto_complete_filter.html.twig';
    }
}
