<?php

declare(strict_types=1);

namespace App\Crudit\Datasource;

use App\Crudit\Datasource\Filterset\InterventionFilterset;
use App\Entity\Intervention;
use Doctrine\ORM\QueryBuilder;
use Lle\CruditBundle\Datasource\AbstractDoctrineDatasource;
use Lle\CruditBundle\Datasource\DatasourceParams;
use Symfony\Contracts\Service\Attribute\Required;

class InterventionDatasource extends AbstractDoctrineDatasource
{
    #[Required]
    public function setFilterset(InterventionFilterset $filterset): void
    {
        $this->filterset = $filterset;
    }

    public function getClassName(): string
    {
        return Intervention::class;
    }

    /**
     * The interventions of another company (reference "OTHER-…") are not ours: as a project restricts its data (lot
     * 3.4.c, the counters count the elements of the datasource).
     */
    public function buildQueryBuilder(?DatasourceParams $requestParams): QueryBuilder
    {
        return parent::buildQueryBuilder($requestParams)
            ->andWhere('root.reference NOT LIKE :other')
            ->setParameter('other', 'OTHER-%');
    }
}
