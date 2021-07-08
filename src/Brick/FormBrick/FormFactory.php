<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\FormBrick;

use Lle\CruditBundle\Brick\AbstractBasicBrickFactory;
use Lle\CruditBundle\Brick\BrickResponse\FlashBrickResponse;
use Lle\CruditBundle\Brick\BrickResponse\RedirectBrickResponse;
use Lle\CruditBundle\Brick\BrickResponseCollector;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Contracts\DatasourceInterface;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Exception\CruditException;
use Lle\CruditBundle\Resolver\ResourceResolver;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FormFactory extends AbstractBasicBrickFactory
{
    /** @var FormFactoryInterface */
    private $formFactory;

    /** @var BrickResponseCollector  */
    private $brickResponseCollector;

    /** @var UrlGeneratorInterface  */
    private $urlGenerator;

    protected PropertyAccessorInterface $propertyAccessor;

    public function __construct(
        ResourceResolver $resourceResolver,
        RequestStack $requestStack,
        FormFactoryInterface $formFactory,
        BrickResponseCollector $brickResponseCollector,
        UrlGeneratorInterface $urlGenerator,
        PropertyAccessorInterface $propertyAccessor
    ) {
        parent::__construct($resourceResolver, $requestStack);
        $this->formFactory = $formFactory;
        $this->brickResponseCollector = $brickResponseCollector;
        $this->urlGenerator = $urlGenerator;
        $this->propertyAccessor = $propertyAccessor;
    }

    public function support(BrickConfigInterface $brickConfigurator): bool
    {
        return get_class($brickConfigurator) === FormConfig::class;
    }

    public function buildView(BrickConfigInterface $brickConfigurator): BrickView
    {
        /** @var FormConfig $brickConfigurator */
        $resource = $this->getResource($brickConfigurator);

        $form = $this->generateForm($brickConfigurator, $resource);
        $this->bindRequest($form, $brickConfigurator, $resource);
        /** @var FormConfig $brickConfigurator */
        $view = new BrickView($brickConfigurator);
        $view
            ->setTemplate('@LleCrudit/brick/form')
            ->setConfig($brickConfigurator->getConfig($this->getRequest()))
            ->setData([
                'form' => $form->createView(),
                'cancel_path' => $brickConfigurator->getCancelPath(),
                ]);
        return $view;
    }

    private function bindRequest(FormInterface $form, FormConfig $brickConfig, object $resource): void
    {
        $form->handleRequest($this->getRequest());
        if ($this->getRequest()->getMethod() === 'POST' and $form->isSubmitted()) {
            if ($form->isValid()) {
                $brickConfig->getDataSource()->save($resource);
                $this->brickResponseCollector->add(
                    new FlashBrickResponse(FlashBrickResponse::SUCCESS, $brickConfig->getMessageSuccess())
                );

                // if the new entity is from a sublist, use parent entity id
                if ($brickConfig->isSublist() && $brickConfig->getAssocProperty()) {
                    $resource = $this->propertyAccessor->getValue($resource, $brickConfig->getAssocProperty());
                }

                $this->brickResponseCollector->add(new RedirectBrickResponse(
                    $this->urlGenerator->generate(
                        $brickConfig->getSuccessRedirectPath()->getRoute(),
                        array_merge($brickConfig->getSuccessRedirectPath()->getParams(), ['id'=> $resource->getId()])
                    )
                ));
            } else {
                $this->brickResponseCollector->add(
                    new FlashBrickResponse(FlashBrickResponse::ERROR, $brickConfig->getMessageError())
                );
            }
        }
    }

    private function generateForm(FormConfig $brickConfigurator, object $resource): FormInterface
    {
        if ($brickConfigurator->getForm() === null) {
            $formBuilder = $this->formFactory->createBuilder(
                FormType::class,
                $resource,
                ['allow_extra_fields' => true]
            );
            foreach ($brickConfigurator->getFields() as $field) {
                $formBuilder->add($field->getName(), $field->getType(), $field->getOptions());
            }
            return $formBuilder->getForm();
        } else {
            $form = $brickConfigurator->getCrudConfig()->getForm($resource);
            if ($form) {
                return $form;
            }
            return $this->formFactory->create($brickConfigurator->getForm(), $resource);
        }
    }

    private function getResource(FormConfig $brickConfigurator): object
    {
        $datasource = $brickConfigurator->getCrudConfig()->getDatasource();

        $resource = null;
        if ($this->getRequest()->get('id')) {
            $resource = $datasource->get($this->getRequest()->get('id'));
        } else {
            $resource = $datasource->newInstance();
        }

        if ($resource === null) {
            throw new CruditException('Resource not found');
        }

        if ($brickConfigurator->isSublist()) {
            $subResource = $brickConfigurator->getDataSource()->newInstance();

            if ($brickConfigurator->getAssocProperty()) {
                $this->propertyAccessor->setValue(
                    $subResource,
                    $brickConfigurator->getAssocProperty(),
                    $resource
                );
            }

            $resource = $subResource;
        }

        return $resource;
    }
}
