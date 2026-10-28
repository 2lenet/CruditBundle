<?php

declare(strict_types=1);

namespace App\Crudit\Datasource;

use App\Crudit\Datasource\Filterset\InterventionFilterset;
use App\Entity\Intervention;
use Lle\CruditBundle\Datasource\AbstractDoctrineDatasource;
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
}
