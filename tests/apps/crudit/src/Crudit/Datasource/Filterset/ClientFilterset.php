<?php

declare(strict_types=1);

namespace App\Crudit\Datasource\Filterset;

use App\Entity\Client;
use Lle\CruditBundle\Datasource\AbstractFilterSet;
use Lle\CruditBundle\Filter\FilterType\BooleanFilterType;
use Lle\CruditBundle\Filter\FilterType\ChoiceFilterType;
use Lle\CruditBundle\Filter\FilterType\EntityFilterType;
use Lle\CruditBundle\Filter\FilterType\NumberFilterType;
use Lle\CruditBundle\Filter\FilterType\NumberRangeFilterType;
use Lle\CruditBundle\Filter\FilterType\PeriodeFilterType;
use Lle\CruditBundle\Filter\FilterType\StringFilterType;

/**
 * The filters of the clients (lot 3.1.e): one of each type of the bundle, a main one in the bar (the name and the status),
 * a hidden one (it shows nowhere) and one with a template of the project (the e-mail).
 */
class ClientFilterset extends AbstractFilterSet
{
    public function getFilters(): array
    {
        return [
            StringFilterType::new('name')->setPrimary(true),
            ChoiceFilterType::new('status', ['client.status.prospect' => 'prospect', 'client.status.customer' => 'customer', 'client.status.lost' => 'lost'], true)->setPrimary(true),
            BooleanFilterType::new('active'),
            NumberFilterType::new('amount'),
            NumberRangeFilterType::new('progress'),
            PeriodeFilterType::new('createdAt'),
            EntityFilterType::new('referrer', Client::class),
            new EmailFilterType('email'),
            StringFilterType::new('notes')->setHidden(true),
        ];
    }
}
