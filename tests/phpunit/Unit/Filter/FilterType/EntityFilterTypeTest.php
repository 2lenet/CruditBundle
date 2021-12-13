<?php

namespace phpunit\Unit\Filter\FilterType;

use Lle\CruditBundle\Filter\FilterType\EntityFilterType;
use PHPUnit\Framework\TestCase;

class EntityFilterTypeTest extends TestCase
{

    public function testDataRoute(): void
    {
        $filterType = new EntityFilterType('test', 'App\\Entity\\Test');
        self::assertEquals('app_crudit_test_autocomplete', $filterType->getDataRoute());
    }

    public function testCustomRoute(): void
    {
        $customRoute = 'lle_hermes_test_autocomplete';
        $filterType = new EntityFilterType('test', 'App\\Entity\\Test::class', $customRoute);
        self::assertEquals($customRoute, $filterType->getDataRoute());
    }
}
