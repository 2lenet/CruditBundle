<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\HistoryBrick;

use Doctrine\ORM\EntityManagerInterface;
use Lle\CruditBundle\Brick\AbstractBasicBrickFactory;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Resolver\ResourceResolver;
use Symfony\Component\HttpFoundation\RequestStack;

class HistoryFactory extends AbstractBasicBrickFactory
{
    private EntityManagerInterface $em;

    public function __construct(
        ResourceResolver       $resourceResolver,
        RequestStack           $requestStack,
        EntityManagerInterface $em
    )
    {
        parent::__construct($resourceResolver, $requestStack);

        $this->em = $em;
    }

    public function support(BrickConfigInterface $brickConfigurator): bool
    {
        return (HistoryConfig::class === get_class($brickConfigurator));
    }

    public function buildView(BrickConfigInterface $brickConfigurator): BrickView
    {
        $view = new BrickView($brickConfigurator);
        $view
            ->setTemplate("@LleCrudit/brick/history")
            ->setData([
                "history" => $this->getLogEntries($brickConfigurator),
            ])
            ->setConfig($brickConfigurator->getConfig($this->getRequest()));

        return $view;
    }

    private function getLogEntries(BrickConfigInterface $brickConfigurator)
    {
        $item = $brickConfigurator
            ->getDataSource()
            ->get($this->getRequest()->get("id"));

        $logs = $this->em
            ->getRepository("Gedmo\Loggable\Entity\LogEntry")
            ->getLogEntries($item);

        $metadata = $this->em->getClassMetadata(get_class($item));
        $history = [];

        foreach ($logs as $log) {
            if ($log->getData()) {
                $data = [];

                foreach ($log->getData() as $property => $value) {
                    $type = $metadata->getTypeOfField($property);
                    $result = $value;
                    $class = false;
                    if ($metadata->hasAssociation($property) && is_array($value) && $value) {
                        $type = $metadata->isSingleValuedAssociation($property) ? "single_assoc" : "multi_assoc";
                        $association = $metadata->getAssociationMapping($property);

                        $subItem = $this->em->getRepository($association["targetEntity"])->findOneBy($value);
                        if ($subItem) {
                            $result = (string)$subItem;
                            $namespace = $this->em->getClassMetadata(get_class($subItem))->getName();
                            $class = explode("\\", $namespace);
                        } else {
                            $result = "?";
                        }
                    } elseif ($type === "boolean") {
                        $result = $value ? "crudit.boolean.yes" : "crudit.boolean.no";
                    } elseif ($type === "date") {
                        $result = $value ? $value->format("d/m/Y") : "";
                    } elseif ($type === "datetime") {
                        $result = $value ? $value->format("d/m/Y H:i:s") : "";
                    } elseif (is_array($value)) {
                        $result = implode(", ", $value);
                    }

                    $data[$property] = [
                        "value" => $result,
                        "type" => $type,
                        "raw" => $value,
                    ];
                    if ($class) {
                        $data[$property] = array_merge($data[$property], ['classname' => strtolower(end($class))]);
                    }
                }
                $history[] = [
                    "log" => $log,
                    "data" => $data,
                ];
            }
        }

        return $history;
    }
}
