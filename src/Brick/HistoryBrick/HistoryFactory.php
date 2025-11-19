<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\HistoryBrick;

use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Loggable\Entity\LogEntry;
use Lle\CruditBundle\Brick\AbstractBasicBrickFactory;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Provider\ConfigProvider;
use Lle\CruditBundle\Resolver\ResourceResolver;
use Symfony\Component\HttpFoundation\RequestStack;

class HistoryFactory extends AbstractBasicBrickFactory
{
    private EntityManagerInterface $em;

    public function __construct(
        ResourceResolver $resourceResolver,
        RequestStack $requestStack,
        EntityManagerInterface $em,
        private ConfigProvider $configProvider,
    ) {
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
        if ($brickConfigurator instanceof HistoryConfig) {
            $view
                ->setTemplate($brickConfigurator->getTemplate() ?? "@LleCrudit/brick/history")
                ->setData([
                    "history" => $this->getLogEntries($brickConfigurator),
                    'title' => $brickConfigurator->getTitle(),
                    'titleCss' => $brickConfigurator->getTitleCss(),
                ])
                ->setConfig($brickConfigurator->getConfig($this->getRequest()));
        }

        return $view;
    }

    private function getLogEntries(BrickConfigInterface $brickConfigurator): array
    {
        $mainDatasource = $brickConfigurator->getDataSource();
        $mainId = $this->getRequest()->get("id");
        /** @var object $item */
        $item = $mainDatasource->get($mainId);
        $logs = $this->getLogEntriesDatasource($item);
        $options = $brickConfigurator->getOptions();
        if (array_key_exists('otherEntities', $options)) {
            foreach ($options['otherEntities'] as $ds) {
                $method = $ds["method"];
                $obj = $item->$method();
                if ($obj !== null) {
                    $subId = (string)$obj->getId();
                    $subitem = $ds["datasource"]->get($subId);
                    $logs2 = $this->getLogEntriesDatasource($subitem);
                    $logs = array_merge($logs, $logs2);
                }
            }
        }

        usort($logs, function ($a, $b) {
            return $b['log']->getLoggedAt()->getTimestamp() <=> $a['log']->getLoggedAt()->getTimestamp();
        });

        return $logs;
    }

    private function getLogEntriesDatasource(object $item): array
    {
        /** @var LogEntry $item */
        $logs = $this->em
            ->getRepository(LogEntry::class)
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
                            $class = get_class($subItem);
                            /** @var \Stringable $subItem */
                            $result = (string)$subItem;
                            $namespace = $this->em->getClassMetadata($class)->getName();
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
                        $data[$property] = array_merge($data[$property], [
                            'classname' => strtolower(end($class)),
                            'crudConfig' => $this->configProvider->getConfigurator(strtoupper(end($class))),
                        ]);
                    }
                }
                $history[] = [
                    "log" => $log,
                    "entity" => basename(str_replace('\\', '/', get_class($item))),
                    "data" => $data,
                ];
            }
        }

        return $history;
    }
}
