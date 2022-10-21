<?php

namespace Lle\CruditBundle\Service\EasyAdminConverter;

use Lle\CruditBundle\Maker\MakeCrudit;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\Filesystem\Filesystem;

/**
 * TODO
 * - warn for custom filter types
 * - choice filter types
 * - fields in show/list
 * - form types ?
 * - labels and other field options
 */
class Converter
{
    protected $logs = [];

    public function __construct(
        protected Generator $generator,
        protected MakeCrudit $cruditMaker,
        protected Filesystem $filesystem,
    )
    {
    }

    public function convert(array $config): iterable
    {
        // TODO REMOVE
        $this->filesystem->remove(["src/Controller"]);
        $this->filesystem->remove(["src/Crudit"]);
        $this->filesystem->remove(["src/Form"]);
        //

        $ignoreDuplicates = [];

        if (isset($config["entities"])) {
            foreach ($config["entities"] as $short => $entityConfig) {

                if (isset($ignoreDuplicates[$entityConfig["class"]])) {
                    // EasyAdmin allows to define multiple CRUDs for a single entity
                    // we don"t bother handling that case in Crudit and simply
                    // warn the user
                    yield "warning" => "Duplicate detected for " . $entityConfig["class"] . " (" . $short . "). Please check the CrudConfig and the FormType.";
                    continue;
                }

                if (isset($entityConfig["filter"])) {
                    yield from $this->makeFilterSet($entityConfig);
                }
                yield from $this->makeDatasource($entityConfig);
                yield from $this->makeCrudConfig($entityConfig);
                yield from $this->makeController($entityConfig);
                yield from $this->makeFormType($entityConfig);

                $ignoreDuplicates[$entityConfig["class"]] = true;
            }
        }

        yield "success" => "Conversion finished ! Please note the warnings above and fix them.";
    }

    protected function makeFilterSet(array $entityConfig): iterable
    {
        $entityClass = $entityConfig["class"];
        $shortEntity = $this->getShortEntityName($entityClass);

        $fields = [];
        foreach ($entityConfig["filter"]["fields"] as $filter) {
            $fields[] = ["name" => $filter["property"]];
        }

        $filtersetClassNameDetails = $this->generator->createClassNameDetails(
            $shortEntity,
            "Crudit\Datasource\Filterset\\",
            "FilterSet"
        );

        $this->generator->generateClass(
            $filtersetClassNameDetails->getFullName(),
            $this->cruditMaker->getSkeletonTemplate("filterset/EntityFilterset.php"),
            [
                "namespace" => "App",
                "entityClass" => $shortEntity,
                "fullEntityClass" => $entityClass,
                "fields" => $fields,
                "strictType" => true,
            ]
        );
        $this->generator->writeChanges();

        yield;
    }

    protected function makeDatasource(array $entityConfig): iterable
    {
        $entityClass = $entityConfig["class"];
        $shortEntity = $this->getShortEntityName($entityClass);

        $datasourceClassNameDetails = $this->generator->createClassNameDetails(
            $shortEntity,
            "Crudit\\Datasource\\",
            "Datasource"
        );
        $this->generator->generateClass(
            $datasourceClassNameDetails->getFullName(),
            $this->cruditMaker->getSkeletonTemplate("datasource/DoctrineDatasource.php"),
            [
                "namespace" => "App",
                "entityClass" => $shortEntity,
                "hasFilterset" => isset($entityConfig["filter"]),
                "fullEntityClass" => $entityClass,
                "strictType" => true,
            ]
        );
        $this->generator->writeChanges();

        yield;
    }

    protected function makeCrudConfig(array $entityConfig): iterable
    {
        $entityClass = $entityConfig["class"];
        $shortEntity = $this->getShortEntityName($entityClass);
        $fields = [];


        $fields = [];
        foreach ($entityConfig["list"]["fields"] as $property) {
            $sortable = isset($property["sortable"]) ? $property["sortable"] : true;
            $fields[] = ["name" => $property["property"], "sortable" => $sortable];
        }

        $configuratorClassNameDetails = $this->generator->createClassNameDetails(
            $shortEntity,
            "Crudit\\Config\\",
            "CrudConfig"
        );

        $this->generator->generateClass(
            $configuratorClassNameDetails->getFullName(),
            $this->cruditMaker->getSkeletonTemplate("config/CrudAutoConfig.php"),
            [
                "namespace" => "App",
                "fields" => $fields,
                "entityClass" => $shortEntity,
                "fullEntityClass" => $entityClass,
                "strictType" => true,
                "form" => true,
                "controllerRoute" => $shortEntity,
            ]
        );
        $this->generator->writeChanges();

        yield;
    }

    protected function makeController(array $entityConfig): iterable
    {
        $entityClass = $entityConfig["class"];
        $shortEntity = $this->getShortEntityName($entityClass);

        $controllerClassNameDetails = $this->generator->createClassNameDetails(
            $shortEntity,
            "Controller\\",
            "Controller"
        );
        $this->generator->generateClass(
            $controllerClassNameDetails->getFullName(),
            $this->cruditMaker->getSkeletonTemplate("controller/CrudController.php"),
            [
                "namespace" => "App",
                "fullEntityClass" => $entityClass,
                "entityClass" => $shortEntity,
                "strictType" => true
            ]
        );
        $this->generator->writeChanges();

        yield;
    }

    protected function makeFormType(array $entityConfig): iterable
    {
        $entityClass = $entityConfig["class"];
        $shortEntity = $this->getShortEntityName($entityClass);

        $fields = [];

        if (isset($entityConfig["new"]["fields"])) {
            $formConfig = $entityConfig["new"]["fields"];
        } elseif (isset($entityConfig["edit"]["fields"])) {
            $formConfig = $entityConfig["edit"]["fields"];
        } else {
            return;
        }

        foreach ($formConfig as $property) {
            $fields[] = ["name" => $property["property"]];
        }


        $formTypeClassNameDetails = $this->generator->createClassNameDetails(
            $shortEntity,
            "Form\\",
            "Type"
        );
        $this->generator->generateClass(
            $formTypeClassNameDetails->getFullName(),
            $this->cruditMaker->getSkeletonTemplate("form/EntityCruditType.php"),
            [
                "namespace" => "App",
                "entityClass" => $shortEntity,
                "fullEntityClass" => $entityClass,
                "fields" => $fields,
                "strictType" => true
            ]
        );
        $this->generator->writeChanges();

        yield;
    }

    protected function getShortEntityName(string $class): string
    {
        return basename(str_replace("\\", "/", $class));
    }
}
