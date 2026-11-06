<?php

declare(strict_types=1);

namespace App\Crudit\Datasource\Filterset;

use Lle\CruditBundle\Filter\FilterType\StringFilterType;

/**
 * A filter with a template of the project: it extends the base template of the bundle and replaces its block `input`
 * (the way the projects of the 1.x do). The Filter:Bar includes it as it is.
 */
class EmailFilterType extends StringFilterType
{
    public function getTemplate(): string
    {
        return 'filter/email_filter.html.twig';
    }
}
