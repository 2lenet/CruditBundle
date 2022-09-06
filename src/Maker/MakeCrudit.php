<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Maker;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Lle\CruditBundle\Datasource\AbstractDoctrineDatasource;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;

final class MakeCrudit extends AbstractMaker
{
    /** @var FileManager */
    private $fileManager;

    /** @var DoctrineHelper */
    private $entityHelper;

    /** @var bool */
    private $withController;

    /** @var string */
    private $projectDir;

    public function __construct(
        FileManager    $fileManager,
        DoctrineHelper $entityHelper,
        KernelInterface $kernel
    )
    {
        $this->fileManager = $fileManager;
        $this->entityHelper = $entityHelper;
        $this->projectDir = $kernel->getProjectDir();
    }

    public static function getCommandName(): string
    {
        return 'make:crudit';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->setDescription('Creates a new controller class')
            ->addArgument(
                'entity-class',
                InputArgument::OPTIONAL,
                sprintf(
                    'The class name of the entity to create CRUD (e.g. <fg=yellow>%s</>)',
                    Str::asClassName(Str::getRandomTerm())
                )
            )
            ->addArgument(
                'namespace-controller',
                InputArgument::OPTIONAL,
                sprintf('Namespace for controller App/Controller/[...]/MyController.php ?')
            )
            ->addArgument(
                'filter',
                InputArgument::OPTIONAL,
                sprintf('Do you want some filters ?')
            )
            ->setHelp((string)file_get_contents(__DIR__ . '/../Resources/help/make_crudit.txt'));

        $inputConfig->setArgumentAsNonInteractive('entity-class');
        $inputConfig->setArgumentAsNonInteractive('namespace-controller');
        $inputConfig->setArgumentAsNonInteractive('filter');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if (null === $input->getArgument('entity-class')) {
            $argument = $command->getDefinition()->getArgument('entity-class');
            $entities = $this->entityHelper->getEntitiesForAutocomplete();
            $question = new Question($argument->getDescription());
            $question->setAutocompleterValues($entities);
            $value = $io->askQuestion($question);
            $input->setArgument('entity-class', $value);
        }

        if (null === $input->getArgument('namespace-controller')) {
            $argument = $command->getDefinition()->getArgument('namespace-controller');
            $question = new Question($argument->getDescription(), 'Crudit');
            $finder = new Finder();
            $finder->in($this->projectDir . '/src/Controller/');
            $controllerNamespaces = [null];
            foreach ($finder->directories() as $dir) {
                /* @var SplFileInfo $dir */
                $controllerNamespaces[] = $dir->getBasename();
            }
            $question->setAutocompleterValues($controllerNamespaces);
            $value = $io->askQuestion($question);
            $input->setArgument('namespace-controller', $value);
        }

        if (null === $input->getArgument('filter')) {
            $argument = $command->getDefinition()->getArgument('filter');
            $question = new ConfirmationQuestion($argument->getDescription(), true);
            $value = $io->askQuestion($question);
            $input->setArgument('filter', $value);
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $classname = Validator::entityExists(
            $this->getStringArgument('entity-class', $input),
            $this->entityHelper->getEntitiesForAutocomplete()
        );
        if (strpos($classname, '\\') === false) {
            $classname = "App\\Entity\\" . $classname;
        }
        $io->text('Create a configurator for ' . $classname);
        try {
            $this->createConfigurator($input, $io, $generator, $classname);
        } catch (\Exception $e) {
            $io->error($e->getMessage());
        }

        try {
            $this->createFormType($input, $io, $generator, $classname);
        } catch (\Exception $e) {
            $io->error($e->getMessage());
        }

        if ($this->getBoolArgument('filter', $input)) {
            try {
                $this->createFilterset($input, $io, $generator, $classname);
            } catch (\Exception $e) {
                $io->error($e->getMessage());
            }
        }

        try {
            $this->createDatasource($input, $io, $generator, $classname);
        } catch (\Exception $e) {
            $io->error($e->getMessage());
        }

        try {
            $this->createController($input, $io, $generator);
        } catch (\Exception $e) {
            $io->error($e->getMessage());
        }
    }

    private function getStringArgument(string $name, InputInterface $input): string
    {
        if (is_string($input->getArgument($name)) || is_null($input->getArgument($name))) {
            return (string)$input->getArgument($name);
        }
        throw new InvalidArgumentException($name . ' must be string type');
    }

    private function createConfigurator(
        InputInterface $input,
        ConsoleStyle   $io,
        Generator      $generator,
        string         $entityClass
    ): string
    {
        $fields = $this->getFields($entityClass);

        $template = 'CrudAuto';
        $shortEntity = basename(str_replace('\\', '/', $entityClass));
        $configuratorClassNameDetails = $generator->createClassNameDetails(
            $shortEntity,
            'Crudit\\Config\\',
            'CrudConfig'
        );

        $generator->generateClass(
            $configuratorClassNameDetails->getFullName(),
            $this->getSkeletonTemplate('config/' . $template . 'Config.php'),
            [
                'namespace' => 'App',
                'fields' => $fields,
                'entityClass' => $shortEntity,
                'fullEntityClass' => $entityClass,
                'strictType' => true,
                'form' => true,
                'controllerRoute' => ($input->getArgument('namespace-controller')) ?
                    $this->getStringArgument('namespace-controller', $input) . '_' .
                    $shortEntity :
                    $shortEntity
            ]
        );
        $generator->writeChanges();
        $this->writeSuccessMessage($io);
        return $configuratorClassNameDetails->getFullName();
    }

    private function getFields(string $entityClass): array
    {
        $fields = [];

        $metadata = $this->entityHelper->getMetadata($entityClass);
        if ($metadata instanceof ClassMetadata) {
            foreach ($metadata->getFieldNames() as $fieldname) {
                $fields[] = ['name' => $fieldname, 'sortable' => true];
            }

            foreach ($metadata->getAssociationNames() as $fieldassoc) {
                if ($metadata->getAssociationMapping($fieldassoc)['type'] & ClassMetadataInfo::TO_ONE) {
                    $sortable = true;
                } else {
                    $sortable = false;
                }

                $fields[] = ['name' => $fieldassoc, 'sortable' => $sortable];
            }
        }

        return $fields;
    }

    private function getSkeletonTemplate(string $templateName): string
    {
        return __DIR__ . '/../Resources/skeleton/crud/' . $templateName;
    }

    private function createFormType(
        InputInterface $input,
        ConsoleStyle   $io,
        Generator      $generator,
        string         $entityClass
    ): void
    {
        $fields = $this->getFields($entityClass);
        $shortEntity = basename(str_replace('\\', '/', $entityClass));

        $formTypeClassNameDetails = $generator->createClassNameDetails(
            $shortEntity,
            'Form\\',
            'Type'
        );
        $generator->generateClass(
            $formTypeClassNameDetails->getFullName(),
            $this->getSkeletonTemplate('form/EntityCruditType.php'),
            [
                'namespace' => 'App',
                'entityClass' => $shortEntity,
                'fullEntityClass' => $input->getArgument('entity-class'),
                'fields' => $fields,
                'strictType' => true
            ]
        );
        $generator->writeChanges();
        $this->writeSuccessMessage($io);
    }

    private function getBoolArgument(string $name, InputInterface $input): bool
    {
        if (is_string($input->getArgument($name)) || is_bool($input->getArgument($name))) {
            return (bool)$input->getArgument($name);
        }
        throw new InvalidArgumentException($name . ' must be bool type');
    }

    private function createFilterset(
        InputInterface $input,
        ConsoleStyle   $io,
        Generator      $generator,
        string         $entityClass
    ): void
    {
        $shortEntity = basename(str_replace('\\', '/', $entityClass));

        $fields = $this->getFields($entityClass);
        $filtersetClassNameDetails = $generator->createClassNameDetails(
            $shortEntity,
            'Crudit\Datasource\Filterset\\',
            'FilterSet'
        );
        $generator->generateClass(
            $filtersetClassNameDetails->getFullName(),
            $this->getSkeletonTemplate('filterset/EntityFilterset.php'),
            [
                'namespace' => 'App',
                'entityClass' => $shortEntity,
                'fullEntityClass' => $input->getArgument('entity-class'),
                'fields' => $fields,
                'strictType' => true
            ]
        );
        $generator->writeChanges();
        $this->writeSuccessMessage($io);
    }

    private function createDatasource(InputInterface $input, ConsoleStyle $io, Generator $generator, string $entityClass): void
    {
        if (count(AbstractDoctrineDatasource::getInitSearchFields($entityClass)) == 0) {
            $io->warning("You must set the searchFields property for autocompletion.");
        }
        $shortEntity = basename(str_replace('\\', '/', $this->getStringArgument('entity-class', $input)));

        $datasourceClassNameDetails = $generator->createClassNameDetails(
            $shortEntity,
            'Crudit\\Datasource\\',
            'Datasource'
        );
        $generator->generateClass(
            $datasourceClassNameDetails->getFullName(),
            $this->getSkeletonTemplate('datasource/DoctrineDatasource.php'),
            [
                'namespace' => 'App',
                'entityClass' => $shortEntity,
                'hasFilterset' => $this->getBoolArgument('filter', $input),
                'fullEntityClass' => $this->getStringArgument('entity-class', $input),
                'strictType' => true,
            ]
        );
        $generator->writeChanges();
        $this->writeSuccessMessage($io);
    }

    private function createController(InputInterface $input, ConsoleStyle $io, Generator $generator): string
    {
        $shortEntity = basename(str_replace('\\', '/', $this->getStringArgument('entity-class', $input)));

        $controllerClassNameDetails = $generator->createClassNameDetails(
            $shortEntity,
            $this->getStringArgument('namespace-controller', $input) ?
                'Controller\\' . $this->getStringArgument('namespace-controller', $input) . '\\' :
                'Controller\\',
            'Controller'
        );
        $generator->generateClass(
            $controllerClassNameDetails->getFullName(),
            $this->getSkeletonTemplate('controller/CrudController.php'),
            [
                'namespace' => 'App',
                'fullEntityClass' => $this->getStringArgument('entity-class', $input),
                'entityClass' => $shortEntity,
                'strictType' => true
            ]
        );
        $generator->writeChanges();
        $this->writeSuccessMessage($io);
        return $controllerClassNameDetails->getFullName();
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(
            Annotation::class,
            'annotations'
        );
    }

    private function getPathOfClass(string $class): string
    {
        if (class_exists($class)) {
            return (string)(new \ReflectionClass($class))->getFileName();
        }
        throw new InvalidArgumentException('entity class ' . $class . ' not found');
    }
}
