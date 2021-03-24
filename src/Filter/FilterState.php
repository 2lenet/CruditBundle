<?php
 
namespace Lle\CruditBundle\Filter;
 
use InvalidArgumentException;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Doctrine\ORM\EntityManagerInterface;

class FilterState
{

    protected $filters = [];
    
    private $filterChain;

    public function __construct(EntityManagerInterface $em, FilterChain $filterChain)
    {
        $this->em = $em;
        $this->filterChain = $filterChain;
    }

    public function isFilterLink($request) {
        foreach($request->query->all() as $k => $val) {
            if ( strrpos($k, 'filter_') === 0 ) return true;
        }
        return false;
    }
    /*
    public function bindRequest($request, $crudKey, CrudConfigInterface $config) {
        $reset = false;
        $is_link = $this->isFilterLink($request);
        if ( $is_link || ( $request->request->has('reset') && 'reset' === $request->request->get('reset')) || ( $request->query->has('reset') && 'reset' === $request->query->get('reset')) ) {
            $data[$crudKey] = [];
            $reset = !$is_link;
        } else {
            $data = $request->getSession()->get('admin_filters');
        }
        foreach ($config->getFilters()) {
            $type = $filter['type'] ?? $filter['filter_type'];
            if($this->filterChain->has($type)){
                $filter['config']['data_class'] = $filter['config']['data_class'] ?? $entityConfig['class'];
                $filterObj = $this->filterChain->get($type, $filter, $entityConfig);
            }else {
                throw new \Exception($type." not found");
            }

            if(isset($filter['default'])){
                $filterObj->setDefaults($filter['default']);
            }


            $this->filters[$crudKey][$filter['property']] = $filterObj;
            // set data from sesssion

            $filterObj->setData($data[$crudKey][$filter['property']]??[]);
            // set data from request
            if (!$reset) $filterObj->updateDataFromRequest($request);
            $filterObj->initDefault();

            // save data to session
            $data[$crudKey][$filter['property']] = $filterObj->getData();
        }

        $request->getSession()->set('admin_filters', $data);

    }
    */
    public function getFilters($crudKey) {
        return $this->filters[$crudKey] ?? [];
    }
}
