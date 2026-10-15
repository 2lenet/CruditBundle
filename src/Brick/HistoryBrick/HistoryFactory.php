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
use Lle\CruditUiBundle\Format\Formatter;
use Symfony\Component\HttpFoundation\RequestStack;

class HistoryFactory extends AbstractBasicBrickFactory
{
    private const array DATETIME_TYPES = ["datetime", "datetime_immutable", "datetimetz", "datetimetz_immutable"];

    private EntityManagerInterface $em;

    public function __construct(
        ResourceResolver $resourceResolver,
        RequestStack $requestStack,
        EntityManagerInterface $em,
        private ConfigProvider $configProvider,
        private Formatter $formatter,
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
        $mainId = $this->getRequest()->attributes->get("id");
        /** @var object $item */
        $item = $mainDatasource->get($mainId);
        $logs = [];
        if ($brickConfigurator instanceof HistoryConfig) {
            $logs = $this->getLogEntriesDatasource($item, $brickConfigurator);
            $options = $brickConfigurator->getOptions();
            if (array_key_exists('otherEntities', $options)) {
                foreach ($options['otherEntities'] as $ds) {
                    $method = $ds["method"];
                    $obj = $item->$method();
                    if ($obj !== null) {
                        $subId = (string)$obj->getId();
                        $subitem = $ds["datasource"]->get($subId);
                        $logs2 = $this->getLogEntriesDatasource($subitem, $brickConfigurator);
                        $logs = array_merge($logs, $logs2);
                    }
                }
            }
        }

        usort($logs, function ($a, $b) {
            return $b['log']->getLoggedAt()->getTimestamp() <=> $a['log']->getLoggedAt()->getTimestamp();
        });

        return $logs;
    }

    private function getLogEntriesDatasource(object $item, HistoryConfig $config): array
    {
        /** @var LogEntry $item */

        /** @var class-string<object> $logClass */
        $logClass = $config->getLogEntryClassName() ?? LogEntry::class;
        // @phpstan-ignore-next-line
        $logs = $this->em->getRepository($logClass)->getLogEntries($item);
        $metadata = $this->em->getClassMetadata(get_class($item));
        $history = [];

        foreach ($logs as $log) {
            $data = [];
            if ($log->getData()) {
                /** @var class-string $objectClass */
                $objectClass = $log->getObjectClass();
                $metadata = $this->em->getClassMetadata($objectClass);
                foreach ($log->getData() as $property => $value) {
                    if (is_array($value) && isset($value['date'], $value['timezone'])) {
                        // since DBAL4 dropped the "array" type, LogEntry::$data is stored as JSON:
                        // the \DateTime Gedmo puts in it are read back as arrays
                        $value = new \DateTime($value['date'] . ' ' . $value['timezone']);
                    }
                    $type = $metadata->getTypeOfField($property);
                    $result = $value;
                    $class = false;
                    if ($metadata->hasAssociation($property) && is_array($value) && $value) {
                        $type = $metadata->isSingleValuedAssociation($property) ? "single_assoc" : "multi_assoc";
                        $association = $metadata->getAssociationMapping($property);
                        /** @var class-string $targetEntity */
                        $targetEntity = $association['targetEntity'];

                        $subItem = $this->em->getRepository($targetEntity)->findOneBy($value);
                        if ($subItem) {
                            $class = get_class($subItem);
                            /** @var \Stringable $subItem */
                            $result = (string)$subItem;
                            $namespace = $this->em->getClassMetadata($class)->getName();
                            $class = explode("\\", $namespace);
                        } else {
                            $result = "?";
                        }
                    } elseif ($type === "boolean" || is_bool($value)) {
                        $result = $value ? "crudit.boolean.yes" : "crudit.boolean.no";
                    } elseif (\in_array($type, ["date", "date_immutable"], true)) {
                        // a date alone keeps its day: no conversion to the timezone of Twig
                        $result = $value ? $this->formatter->date($value, 'short', timezone: false) : "";
                    } elseif (\in_array($type, ["time", "time_immutable"], true)) {
                        $result = $value ? $this->formatter->time($value, 'medium', timezone: false) : "";
                    } elseif (\in_array($type, self::DATETIME_TYPES, true) || $value instanceof \DateTimeInterface) {
                        // in the timezone of Twig, as the date of the log entry
                        $result = $value ? $this->formatter->datetime($value, 'short', 'medium') : "";
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
            }
            $history[] = [
                "log" => $log,
                "entity" => basename(str_replace('\\', '/', $log->getObjectClass())),
                "data" => $data,
            ];
        }

        return $history;
    }
}
