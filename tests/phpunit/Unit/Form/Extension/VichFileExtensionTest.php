<?php

declare(strict_types=1);

namespace phpunit\Unit\Form\Extension;

use Lle\CruditBundle\Form\Extension\VichFileExtension;
use Metadata\AdvancedMetadataFactoryInterface;
use Metadata\ClassHierarchyMetadata;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\FormView;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;
use Vich\UploaderBundle\Mapping\PropertyMappingResolverInterface;
use Vich\UploaderBundle\Metadata\ClassMetadata;
use Vich\UploaderBundle\Metadata\MetadataReader;

/**
 * The file already sent of a file of VichUploaderBundle (lot 3.2.c): read from the mapping only (the stored name, the
 * original name, the size, the type), never from the storage.
 */
class VichFileExtensionTest extends TestCase
{
    public function testTheNameIsTheOriginalNameElseTheCleanedNameElseTheStoredName(): void
    {
        $document = new VichFileExtensionDocument('plan-6b05c3.pdf', 'Plan of the site.pdf', 1572864, 'application/pdf');
        self::assertSame(
            ['name' => 'Plan of the site.pdf', 'size' => 1572864, 'mime_type' => 'application/pdf'],
            $this->current($document, ['filename' => 'plan.pdf']),
            'the original name, when the mapping keeps it',
        );

        $withoutOriginal = new VichFileExtensionDocument('plan-6b05c3.pdf', null, 1572864, 'application/pdf');
        self::assertSame('plan.pdf', $this->current($withoutOriginal, ['filename' => 'plan.pdf'])['name'] ?? null, 'else the name cleaned by the FileType of Crudit');
        self::assertSame('plan-6b05c3.pdf', $this->current($withoutOriginal)['name'] ?? null, 'else the stored name');
    }

    public function testASizeThatIsNotANumberAndAMappingWithoutSizeNorType(): void
    {
        $document = new VichFileExtensionDocument('plan.pdf', null, 'unknown', null);
        self::assertSame(['name' => 'plan.pdf', 'size' => null, 'mime_type' => null], $this->current($document));

        self::assertSame(
            ['name' => 'plan.pdf', 'size' => null, 'mime_type' => null],
            $this->current(new VichFileExtensionDocument('plan.pdf', null, 300, 'application/pdf'), [], 'bare'),
            'a mapping that keeps neither the size nor the type',
        );
    }

    public function testNoFileWithoutStoredNameNorMappingNorObject(): void
    {
        self::assertNull($this->current(new VichFileExtensionDocument(null, 'plan.pdf', 300, 'application/pdf')), 'no stored name: no file');
        self::assertNull($this->current(new VichFileExtensionDocument('', null, null, null)));
        self::assertNull($this->current(new VichFileExtensionDocument('plan.pdf', null, null, null), [], 'other'), 'a field without mapping');

        // The prototype of a collection, or a new line: its parent has no data
        $form = Forms::createFormFactory()->createBuilder(FormType::class, null, ['data_class' => null])->add('file', FileType::class)->getForm();
        $view = new FormView();
        $this->extension()->buildView($view, $form->get('file'), []);
        self::assertNull($view->vars['crudit_file_current']);
    }

    /**
     * The variable crudit_file_current of the field $field of $document, after the variables $vars of the type.
     *
     * @param array<string, mixed> $vars
     *
     * @return array<string, mixed>|null
     */
    private function current(VichFileExtensionDocument $document, array $vars = [], string $field = 'file'): ?array
    {
        $form = Forms::createFormFactory()->createBuilder(FormType::class, $document, ['data_class' => null])->add($field, FileType::class, ['mapped' => false])->getForm();
        $view = new FormView();
        $view->vars = $vars;
        $this->extension()->buildView($view, $form->get($field), []);

        /** @var array<string, mixed>|null $current */
        $current = $view->vars['crudit_file_current'];

        return $current;
    }

    private function extension(): VichFileExtension
    {
        $metadata = new ClassMetadata(VichFileExtensionDocument::class);
        $metadata->fields = [
            'file' => ['mapping' => 'documents', 'propertyName' => 'file', 'fileNameProperty' => 'name', 'size' => 'size', 'mimeType' => 'mimeType', 'originalName' => 'originalName'],
            'bare' => ['mapping' => 'documents', 'propertyName' => 'bare', 'fileNameProperty' => 'name', 'size' => null, 'mimeType' => null, 'originalName' => null],
        ];
        $hierarchy = new ClassHierarchyMetadata();
        $hierarchy->addClassMetadata($metadata);
        $reader = $this->createStub(AdvancedMetadataFactoryInterface::class);
        $reader->method('getMetadataForClass')->willReturn($hierarchy);
        $resolver = new class implements PropertyMappingResolverInterface {
            public function resolve(object|array $obj, string $fieldName, array $mappingData): PropertyMapping
            {
                return new PropertyMapping($mappingData['propertyName'], $mappingData['fileNameProperty'], [
                    'size' => $mappingData['size'],
                    'mimeType' => $mappingData['mimeType'],
                    'originalName' => $mappingData['originalName'],
                ]);
            }
        };

        return new VichFileExtension(new PropertyMappingFactory(new MetadataReader($reader), $resolver));
    }
}

/**
 * An entity with a file of VichUploaderBundle in VichFileExtensionTest: the properties its mapping reads.
 */
class VichFileExtensionDocument
{
    public mixed $file = null;

    public mixed $bare = null;

    public mixed $other = null;

    public function __construct(
        public ?string $name,
        public ?string $originalName,
        public mixed $size,
        public ?string $mimeType,
    ) {
    }
}
