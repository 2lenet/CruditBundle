<?php

declare(strict_types=1);

namespace phpunit\Unit\Form\Type;

use Lle\CruditBundle\Form\Type\FileType;
use Metadata\AdvancedMetadataFactoryInterface;
use Metadata\ClassHierarchyMetadata;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Vich\UploaderBundle\Handler\UploadHandler;
use Vich\UploaderBundle\Injector\FileInjectorInterface;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;
use Vich\UploaderBundle\Mapping\PropertyMappingResolverInterface;
use Vich\UploaderBundle\Metadata\ClassMetadata;
use Vich\UploaderBundle\Metadata\MetadataReader;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * The FileType of Crudit: download_route and image_route generated with the id of the entity that holds the file (a
 * line of a collection: its own), the name cleaned from the stored name of the mapping.
 */
class FileTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        $metadata = new ClassMetadata(FileTypeDocument::class);
        $metadata->fields = ['file' => ['mapping' => 'documents', 'propertyName' => 'file', 'fileNameProperty' => 'name']];
        $hierarchy = new ClassHierarchyMetadata();
        $hierarchy->addClassMetadata($metadata);
        $reader = $this->createStub(AdvancedMetadataFactoryInterface::class);
        $reader->method('getMetadataForClass')->willReturn($hierarchy);
        $resolver = new class implements PropertyMappingResolverInterface {
            public function resolve(object|array $obj, string $fieldName, array $mappingData): PropertyMapping
            {
                return new PropertyMapping($mappingData['propertyName'], $mappingData['fileNameProperty']);
            }
        };
        $factory = new PropertyMappingFactory(new MetadataReader($reader), $resolver);

        $storage = $this->createStub(StorageInterface::class);
        // A signed url of a storage: never read for the name
        $storage->method('resolveUri')->willReturnCallback(static fn (FileTypeDocument $object): ?string => null === $object->name ? null : 'https://bucket.example/' . $object->name . '?X-Amz-Signature=abc');
        $urls = $this->createStub(UrlGeneratorInterface::class);
        $urls->method('generate')->willReturnCallback(static fn (string $route, array $parameters): string => '/' . $route . '/' . $parameters['id']);
        $handler = new UploadHandler($factory, $storage, $this->createStub(FileInjectorInterface::class), $this->createStub(EventDispatcherInterface::class));

        return [new PreloadedExtension([new FileType($urls, $storage, $handler, $factory)], [])];
    }

    public function testTheRoutesGetTheIdOfTheEntityOfTheFieldAndTheNameIsCleanedFromTheStoredName(): void
    {
        $view = $this->factory->createBuilder(FormType::class, new FileTypeDocument(12, 'plan-6b05c3e1.pdf'), ['data_class' => FileTypeDocument::class])
            ->add('file', FileType::class, ['download_route' => 'download', 'image_route' => 'thumbnail'])
            ->getForm()
            ->createView();

        self::assertSame('/download/12', $view['file']->vars['download_uri']);
        self::assertSame('/thumbnail/12', $view['file']->vars['image_uri']);
        self::assertSame('plan.pdf', $view['file']->vars['filename'], 'from the stored name, not from the signed url');
    }

    public function testEachLineOfACollectionLinksToItsOwnFile(): void
    {
        $view = $this->factory->createBuilder(FormType::class, ['lines' => [new FileTypeDocument(3, 'front-a1.jpg'), new FileTypeDocument(5, 'back-b2.jpg')]])
            ->add('lines', CollectionType::class, [
                'entry_type' => FileTypeLineType::class,
                'allow_add' => true,
            ])
            ->getForm()
            ->createView();

        self::assertSame('/download/3', $view['lines'][0]['file']->vars['download_uri'], 'not the id of the page');
        self::assertSame('/download/5', $view['lines'][1]['file']->vars['download_uri']);
        self::assertSame('back.jpg', $view['lines'][1]['file']->vars['filename']);
        $prototype = $view['lines']->vars['prototype'];
        self::assertInstanceOf(FormView::class, $prototype);
        self::assertNull($prototype['file']->vars['download_uri'], 'the prototype has no entity');
    }

    public function testWithoutIdNoLinkFromTheRoutes(): void
    {
        $view = $this->factory->createBuilder(FormType::class, new FileTypeDocument(null, null), ['data_class' => FileTypeDocument::class])
            ->add('file', FileType::class, ['download_route' => 'download', 'image_route' => 'thumbnail'])
            ->getForm()
            ->createView();

        self::assertNull($view['file']->vars['download_uri']);
        self::assertArrayNotHasKey('image_uri', $view['file']->vars);
        self::assertArrayNotHasKey('filename', $view['file']->vars);
    }

    public function testWithoutImageRouteThePreviewIsTheUriOfTheStorage(): void
    {
        $view = $this->factory->createBuilder(FormType::class, new FileTypeDocument(12, 'photo.jpg'), ['data_class' => FileTypeDocument::class])
            ->add('file', FileType::class)
            ->getForm()
            ->createView();

        self::assertSame('https://bucket.example/photo.jpg?X-Amz-Signature=abc', $view['file']->vars['image_uri']);
        self::assertSame('photo.jpg', $view['file']->vars['filename'], 'a stored name without unique part is kept');
    }
}

/**
 * An entity with a file of VichUploaderBundle in FileTypeTest.
 */
class FileTypeDocument
{
    public mixed $file = null;

    public function __construct(
        public ?int $id,
        public ?string $name,
    ) {
    }
}

/**
 * A line of a collection in FileTypeTest: its file, downloaded by the route of the application.
 *
 * @extends \Symfony\Component\Form\AbstractType<FileTypeDocument>
 */
class FileTypeLineType extends \Symfony\Component\Form\AbstractType
{
    public function buildForm(\Symfony\Component\Form\FormBuilderInterface $builder, array $options): void
    {
        $builder->add('file', FileType::class, ['download_route' => 'download']);
    }

    public function configureOptions(\Symfony\Component\OptionsResolver\OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', FileTypeDocument::class);
    }
}
