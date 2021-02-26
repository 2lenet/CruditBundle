<?php

interface CrudConfigInterface
{
    public static function getDataSource(): DataSourceInterface;
    
    /**
     * @return FieldInterface[]
     * @psalm-return iterable<FieldInterface>
     */
    public function configureFields(string $pageName): iterable;
    
}
