<?php

namespace Lle\CruditBundle\Form\Type;

use Lle\CruditBundle\Contracts\SanitizerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Vich\UploaderBundle\Form\Type\VichFileType;
use Vich\UploaderBundle\Handler\UploadHandler;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;
use Vich\UploaderBundle\Storage\StorageInterface;

class FileType extends VichFileType
{
    protected SanitizerInterface $sanitizer;
    protected UrlGeneratorInterface $urlGenerator;

    public function __construct(SanitizerInterface $sanitizer, UrlGeneratorInterface $urlGenerator, StorageInterface $storage, UploadHandler $handler, PropertyMappingFactory $factory, PropertyAccessorInterface $propertyAccessor = null)
    {
        parent::__construct($storage, $handler, $factory, $propertyAccessor);
        $this->sanitizer = $sanitizer;
        $this->urlGenerator = $urlGenerator;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        if ($form->getParent() && $form->getParent()->getData() && $form->getParent()->getData()->getId()) {
            if ($options['download_route']) {
                $url = $this->urlGenerator->generate(
                    $options['download_route'],
                    ['id' => $form->getParent()->getData()->getId()]
                );
                $view->vars['download_uri'] = $url;
            }

            $filePath = $this->resolveUriOption(true, $form->getParent()->getData(), $form);
            $view->vars['image_uri'] = $filePath;

            $filename = substr($filePath, strrpos($filePath, '/') + 1);
            $filename = substr($filename, 0, strrpos($filename, '-')) . '.' . substr($filename, strrpos($filename, '.') + 1);
            $view->vars['filename'] = $filename;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('required', false);
        $resolver->setDefault('allow_delete', false);
        $resolver->setDefault('download_route', null);
    }

    public function getName(): string
    {
        return 'crudit_file';
    }

    public function getBlockPrefix(): string
    {
        return 'crudit_file';
    }
}
