<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Maker;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Exception\InvalidArgumentException;

final class MakeCrudit extends AbstractMaker
{

    /** @var FileManager */
    private $fileManager;

    /** @var DoctrineHelper */
    private $entityHelper;

    /** @var bool */
    private $withController;

    public function __construct(
        FileManager $fileManager,
        DoctrineHelper $entityHelper
    ) {
        $this->fileManager = $fileManager;
        $this->entityHelper = $entityHelper;
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
                'form',
                InputArgument::OPTIONAL,
                sprintf('Do you want custom your FormType ?')
            )
            ->addArgument(
                'use-strict-type',
                InputArgument::OPTIONAL,
                sprintf('Do you use strict type ?')
            )
            ->setHelp((string) file_get_contents(__DIR__ . '/../Resources/help/make_crudit.txt'))
        ;

        $inputConfig->setArgumentAsNonInteractive('entity-class');
        $inputConfig->setArgumentAsNonInteractive('use-strict-type');
        $inputConfig->setArgumentAsNonInteractive('namespace-controller');
        $inputConfig->setArgumentAsNonInteractive('form');
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

        if (null === $input->getArgument('use-strict-type')) {
            $argument = $command->getDefinition()->getArgument('use-strict-type');
            $question = new ConfirmationQuestion($argument->getDescription(), true);
            $value = $io->askQuestion($question);
            $input->setArgument('use-strict-type', $value);
        }

        if (null === $input->getArgument('namespace-controller')) {
            $argument = $command->getDefinition()->getArgument('namespace-controller');
            $question = new Question($argument->getDescription(), 'Crudit');
            $finder = $this->fileManager->createFinder('src/Controller/');
            $controllerNamespaces = [null];
            foreach ($finder->directories() as $dir) {
                /* @var SplFileInfo $dir */
                $controllerNamespaces[] = $dir->getBasename();
            }
            $question->setAutocompleterValues($controllerNamespaces);
            $value = $io->askQuestion($question);
            $input->setArgument('namespace-controller', $value);
        }

        if (null === $input->getArgument('form')) {
            $argument = $command->getDefinition()->getArgument('form');
            $question = new ConfirmationQuestion($argument->getDescription(), true);
            $value = $io->askQuestion($question);
            $input->setArgument('form', $value);
        }
    }

    private function getFields(ClassNameDetails $entityClassDetail): array
    {
        $fields = [];
        $metadata = $this->entityHelper->getMetadata($entityClassDetail->getFullName());
        if ($metadata instanceof ClassMetadata) {
            foreach ($metadata->getFieldNames() as $fieldname) {
                $fields[] = $fieldname;
            }
        }
        return $fields;
    }

    private function createConfigurator(
        InputInterface $input,
        ConsoleStyle $io,
        Generator $generator,
        ClassNameDetails $entityClassDetail
    ): string {
        $fields = $this->getFields($entityClassDetail);
        $template = 'CrudAuto';
        $configuratorClassNameDetails = $generator->createClassNameDetails(
            $this->getStringArgument('entity-class', $input),
            'Crudit\\Config\\',
            'CrudConfig'
        );
        $generator->generateClass(
            $configuratorClassNameDetails->getFullName(),
            $this->getSkeletonTemplate('config/' . $template . 'Config.php'),
            [
                'namespace' => 'App',
                'fields' => $fields,
                'entityClass' => $input->getArgument('entity-class'),
                'strictType' => $this->getBoolArgument('use-strict-type', $input),
                'form' => $this->getBoolArgument('form', $input),
                'controllerRoute' => ($input->getArgument('namespace-controller')) ?
                    $this->getStringArgument('namespace-controller', $input) . '_' .
                    $this->getStringArgument('entity-class', $input) :
                    $this->getStringArgument('entity-class', $input)
            ]
        );
        $generator->writeChanges();
        $this->writeSuccessMessage($io);
        return $configuratorClassNameDetails->getFullName();
    }

    private function createController(InputInterface $input, ConsoleStyle $io, Generator $generator): string
    {
        $controllerClassNameDetails = $generator->createClassNameDetails(
            $this->getStringArgument('entity-class', $input),
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
                'entityClass' => $this->getStringArgument('entity-class', $input),
                'strictType' => $this->getBoolArgument('use-strict-type', $input)
            ]
        );
        $generator->writeChanges();
        $this->writeSuccessMessage($io);
        return  $controllerClassNameDetails->getFullName();
    }

    private function createFormType(
        InputInterface $input,
        ConsoleStyle $io,
        Generator $generator,
        ClassNameDetails $entityClassDetail
    ): void {
        $fields = $this->getFields($entityClassDetail);
        $formTypeClassNameDetails = $generator->createClassNameDetails(
            $this->getStringArgument('entity-class', $input),
            'Form\\',
            'Type'
        );
        $generator->generateClass(
            $formTypeClassNameDetails->getFullName(),
            $this->getSkeletonTemplate('form/EntityCruditType.php'),
            [
                'namespace' => 'App',
                'entityClass' => $input->getArgument('entity-class'),
                'fields' => $fields,
                'strictType' => $this->getBoolArgument('use-strict-type', $input)
            ]
        );
        $generator->writeChanges();
        $this->writeSuccessMessage($io);
    }

    private function createDatasource(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $datasourceClassNameDetails = $generator->createClassNameDetails(
            $this->getStringArgument('entity-class', $input),
            'Crudit\\Datasource\\',
            'Datasource'
        );
        $generator->generateClass(
            $datasourceClassNameDetails->getFullName(),
            $this->getSkeletonTemplate('datasource/DoctrineDatasource.php'),
            [
                'namespace' => 'App',
                'entityClass' => $this->getStringArgument('entity-class', $input),
                'strictType' => $this->getBoolArgument('use-strict-type', $input)
            ]
        );
        $generator->writeChanges();
        $this->writeSuccessMessage($io);
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $entityClassDetails = $generator->createClassNameDetails(
            Validator::entityExists(
                $this->getStringArgument('entity-class', $input),
                $this->entityHelper->getEntitiesForAutocomplete()
            ),
            'Entity\\'
        );

        $entityPath = $this->getPathOfClass($entityClassDetails->getFullName());

        $io->text('Creat a configurator for ' . $entityClassDetails->getFullName());


        $this->createConfigurator($input, $io, $generator, $entityClassDetails);
        $this->createFormType($input, $io, $generator, $entityClassDetails);
        $this->createDatasource($input, $io, $generator);
        $this->createController($input, $io, $generator);
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(
            Annotation::class,
            'annotations'
        );
    }

    private function getSkeletonTemplate(string $templateName): string
    {
        return __DIR__ . '/../Resources/skeleton/crud/' . $templateName;
    }

    private function getPathOfClass(string $class): string
    {
        if (class_exists($class)) {
            return (string) (new \ReflectionClass($class))->getFileName();
        }
        throw new InvalidArgumentException('entity class ' . $class . ' not found');
    }

    private function getStringArgument(string $name, InputInterface $input): string
    {
        if (is_string($input->getArgument($name)) || is_null($input->getArgument($name))) {
            return (string) $input->getArgument($name);
        }
        throw new InvalidArgumentException($name . ' must be string type');
    }

    private function getBoolArgument(string $name, InputInterface $input): bool
    {
        if (is_string($input->getArgument($name)) || is_bool($input->getArgument($name))) {
            return (bool) $input->getArgument($name);
        }
        throw new InvalidArgumentException($name . ' must be bool type');
    }
}
