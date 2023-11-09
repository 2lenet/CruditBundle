<?php

namespace Lle\CruditBundle\Form\Type;

use Lle\CruditBundle\Contracts\SanitizerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
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
    protected ?Request $request;

    public function __construct(
        SanitizerInterface $sanitizer,
        UrlGeneratorInterface $urlGenerator,
        StorageInterface $storage,
        UploadHandler $handler,
        PropertyMappingFactory $factory,
        PropertyAccessorInterface $propertyAccessor = null,
        RequestStack $requestStack,
    ) {
        parent::__construct($storage, $handler, $factory, $propertyAccessor);

        $this->sanitizer = $sanitizer;
        $this->urlGenerator = $urlGenerator;
        $this->request = $requestStack->getMainRequest();
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        if ($form->getParent() && $form->getParent()->getData() && $this->request && $this->request->attributes->has("id")) {
            if ($options['download_route']) {
                $url = $this->urlGenerator->generate(
                    $options['download_route'],
                    ['id' => $this->request->attributes->get("id")]
                );
                $view->vars['download_uri'] = $url;
            }

            /** @var string $filePath */
            $filePath = $this->resolveUriOption(true, $form->getParent()->getData(), $form);
            $view->vars['image_uri'] = $filePath;

            $filename = substr((string)$filePath, strrpos((string)$filePath, '/') + 1);

            /** @var int $pos */
            $pos = strrpos($filename, '-');

            $filename = substr($filename, 0, $pos) . '.' . substr(
                $filename,
                strrpos($filename, '.') + 1
            );
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
