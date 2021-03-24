<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Symfony\Component\HttpFoundation\Request;

/**
 * StringFilterType
 */
class TreeFilterType extends EntityFilterType
{
    protected $startLevel;

     /**
     * @param string $columnName The column name
     * @param string $alias      The alias
     */
    public function configure(array $config = [])
    {
        if(!isset($config['method'])) $config['method'] = 'getChildren';
        parent::configure($config);
        $this->startLevel = $config['start_level'] ?? 1;
    }


    /**
     * @param array  $data     The data
     * @param string $uniqueId The unique identifier
     */
    public function apply($queryBuiler)
    {   
        if (isset($data['value'])) {
            if($this->getMultiple()){
              $nodes = $this->em->getRepository($this->table)->findById($data['value']);
              $ids = array();
              foreach($nodes as $node){
                $ids[] = $node;
                $children = $this->em->getRepository($this->table)->children($node,false);
                foreach($children as $child) $ids[] = $child->getId();
              }

            }else{
              $node = $this->em->getRepository($this->table)->find($data['value']);
              $children = $this->em->getRepository($this->table)->children($node,false);
              $ids = array($node);
              foreach($children as $child){
                  $ids[] = $child->getId();
              }
            }
            $queryBuilder->andWhere($queryBuilder->expr()->in($alias . $col, $ids));
        }
    }

    public function getEntities($data){
        $em = $this->em; 
        $m = $this->method;
        $elements = $em->getRepository($this->table)->$m();
        return $elements;
    }

    public function getValueEntity($entity){
      $return = null;
      if($this->display($entity)){
        for($i = $this->startLevel; $i < $entity->getLvl();$i++) $return .= '-';
        return $return.$entity->__toString();
      }
    }

    public function display($entity){
      return ($entity->getLvl() >= $this->startLevel);
    }

    public function getTemplate(){
        return '@LleEasyAdminPlus/filter/type/tree_filter.html.twig';
    }

}
