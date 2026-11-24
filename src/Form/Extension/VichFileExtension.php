<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Form\Extension;

use Lle\CruditBundle\Form\Type\FileType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Vich\UploaderBundle\Form\Type\VichFileType;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * The file already sent of a file of VichUploaderBundle (lot 3.2.c), shown by the file of CruditUiBundle: variable
 * `crudit_file_current` ({name, size, mime_type}, or null without file). The name is the original name when the mapping
 * keeps it, else the name the Crudit FileType cleans (as in 1.x), else the stored name; the size and the type only when
 * the mapping keeps them (properties size and mimeType of the UploadableField). The links (download_uri, image_uri)
 * are those of the types, read by the form theme.
 */
class VichFileExtension extends AbstractTypeExtension
{
    public function __construct(
        private readonly StorageInterface $storage,
        private readonly PropertyMappingFactory $factory,
    ) {
    }

    public static function getExtendedTypes(): iterable
    {
        return [VichFileType::class, VichImageType::class, FileType::class];
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['crudit_file_current'] = null;
        $object = $form->getParent()?->getData();
        if (!\is_object($object)) {
            return;
        }
        $field = $form->getConfig()->getOption('property_path') ?? $form->getName();
        $field = (string) $field;
        $mapping = $this->factory->fromField($object, $field);
        $uri = $this->storage->resolveUri($object, $field);
        if (null === $mapping || null === $uri || '' === $uri) {
            return;
        }
        $original = $mapping->readProperty($object, 'originalName');
        $stored = $mapping->getFileName($object);
        $name = \is_string($original) && '' !== $original ? $original : ($view->vars['filename'] ?? $stored ?? basename($uri));
        $size = $mapping->readProperty($object, 'size');
        $mimeType = $mapping->readProperty($object, 'mimeType');

        $view->vars['crudit_file_current'] = [
            'name' => (string) $name,
            'size' => is_numeric($size) ? (int) $size : null,
            'mime_type' => \is_string($mimeType) ? $mimeType : null,
            'uri' => $uri,
        ];
    }
}
