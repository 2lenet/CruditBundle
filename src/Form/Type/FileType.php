<?php

namespace Lle\CruditBundle\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Vich\UploaderBundle\Form\Type\VichFileType;
use Vich\UploaderBundle\Handler\UploadHandler;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * The file of VichUploaderBundle with the routes of the application: download_route (the link Download) and image_route
 * (the preview, for private files), generated with the id of the entity that holds the file (the entity of the page, or
 * its own for a line of a collection or a sub-form). Without id (a line not saved yet), no link from these routes.
 * Without image_route, the preview is the uri of the storage. The variable filename is the stored name without the
 * unique part added by the namer ("plan-6b05….pdf" → "plan.pdf"), as in 1.x.
 */
class FileType extends VichFileType
{
    protected UrlGeneratorInterface $urlGenerator;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        StorageInterface $storage,
        UploadHandler $handler,
        PropertyMappingFactory $factory,
        ?PropertyAccessorInterface $propertyAccessor = null,
    ) {
        parent::__construct($storage, $handler, $factory, $propertyAccessor);

        $this->urlGenerator = $urlGenerator;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        $object = $form->getParent()?->getData();
        if (!\is_object($object)) {
            return;
        }

        $id = $this->propertyAccessor->isReadable($object, 'id') ? $this->propertyAccessor->getValue($object, 'id') : null;
        if (null !== $id && '' !== $id) {
            if ($options['download_route']) {
                $view->vars['download_uri'] = $this->urlGenerator->generate($options['download_route'], ['id' => $id]);
            }
            if ($options['image_route']) {
                $view->vars['image_uri'] = $this->urlGenerator->generate($options['image_route'], ['id' => $id]);
            }
        }
        if (!$options['image_route']) {
            $view->vars['image_uri'] = $this->resolveUriOption(true, $object, $form);
        }

        // From the mapping, not from the uri of the storage (a signed url ends with a query string)
        $stored = $this->factory->fromField($object, $this->getFieldName($form))?->getFileName($object);
        if (\is_string($stored) && '' !== $stored) {
            $view->vars['filename'] = $this->cleanName($stored);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('required', false);
        $resolver->setDefault('allow_delete', false);
        $resolver->setDefault('download_route', null);
        $resolver->setDefault('image_route', null);
    }

    public function getName(): string
    {
        return 'crudit_file';
    }

    public function getBlockPrefix(): string
    {
        return 'crudit_file';
    }

    /**
     * The stored name without what follows its last "-" (the unique part of the namer), with its extension.
     */
    protected function cleanName(string $stored): string
    {
        $filename = basename($stored);
        $dash = strrpos($filename, '-');
        $dot = strrpos($filename, '.');
        if (false === $dash || 0 === $dash) {
            return $filename;
        }

        return substr($filename, 0, $dash) . (false === $dot || $dot < $dash ? '' : '.' . substr($filename, $dot + 1));
    }
}
