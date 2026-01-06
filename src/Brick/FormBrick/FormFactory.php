<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\FormBrick;

use Lle\CruditBundle\Brick\AbstractBasicBrickFactory;
use Lle\CruditBundle\Brick\BrickResponse\FlashBrickResponse;
use Lle\CruditBundle\Brick\BrickResponse\RedirectBrickResponse;
use Lle\CruditBundle\Brick\BrickResponseCollector;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Exception\CruditException;
use Lle\CruditBundle\Resolver\ResourceResolver;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FormFactory extends AbstractBasicBrickFactory
{
    private FormFactoryInterface $formFactory;

    private BrickResponseCollector $brickResponseCollector;

    private UrlGeneratorInterface $urlGenerator;

    protected PropertyAccessorInterface $propertyAccessor;

    public function __construct(
        ResourceResolver $resourceResolver,
        RequestStack $requestStack,
        FormFactoryInterface $formFactory,
        BrickResponseCollector $brickResponseCollector,
        UrlGeneratorInterface $urlGenerator,
        PropertyAccessorInterface $propertyAccessor,
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
        $request = $this->requestStack->getMainRequest();
        $referer = $request?->headers->get('referer');

        /** @var FormConfig $brickConfigurator */
        $resource = $this->getResource($brickConfigurator);

        $form = $this->generateForm($brickConfigurator, $resource);
        $this->bindRequest($form, $brickConfigurator, $resource);
        /** @var FormConfig $brickConfigurator */
        $view = new BrickView($brickConfigurator);
        $view
            ->setTemplate($brickConfigurator->getTemplate() ?? '@LleCrudit/brick/form')
            ->setConfig($brickConfigurator->getConfig($this->getRequest()))
            ->setData([
                'title' => $brickConfigurator->getTitle(),
                'titleCss' => $brickConfigurator->getTitleCss(),
                'form' => $form->createView(),
                'resource' => $resource,
                'options' => $brickConfigurator->getOptions(),
                'cancel_path' => $brickConfigurator->getCancelPath(),
                'referer' => $referer,
            ]);

        return $view;
    }

    private function bindRequest(FormInterface $form, FormConfig $brickConfig, object $resource): void
    {
        $form->handleRequest($this->getRequest());
        if ($this->getRequest()->getMethod() === 'POST' && $form->isSubmitted()) {
            if ($form->isValid()) {
                if ($brickConfig->getDataSource()->save($resource)) {
                    $this->brickResponseCollector->add(
                        new FlashBrickResponse(FlashBrickResponse::SUCCESS, $brickConfig->getMessageSuccess())
                    );
                }

                // if the new entity is from a sublist, use parent entity id
                if ($brickConfig->isSublist() && $brickConfig->getAssocProperty()) {
                    $resource = $this->propertyAccessor->getValue($resource, $brickConfig->getAssocProperty());
                }

                $redirectPath = $this->getRedirectPath($brickConfig, $resource);
                $this->brickResponseCollector->add(new RedirectBrickResponse($redirectPath));
            } else {
                $this->addFlash(FlashBrickResponse::ERROR, $brickConfig->getMessageError());
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

            /** @var class-string<FormTypeInterface<object>> $formu */
            $formu = $brickConfigurator->getForm();
            return $this->formFactory->create($formu, $resource);
        }
    }

    private function getResource(FormConfig $brickConfigurator): object
    {
        $datasource = $brickConfigurator->getCrudConfig()->getDatasource();

        $resource = null;
        if ($this->getRequest()->attributes->get('id')) {
            $resource = $datasource->get($this->getRequest()->attributes->get('id'));
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

    private function addFlash(string $type, string $message): void
    {
        $request = $this->getRequest();

        if (method_exists($request->getSession(), "getFlashBag")) {
            $request->getSession()->getFlashBag()->add($type, $message);
        }
    }

    private function getRedirectPath(FormConfig $brickConfig, mixed $resource): string
    {
        if ($successRedirectPath = $brickConfig->getSuccessRedirectPath()) {
            return $this->urlGenerator->generate(
                $successRedirectPath->getRoute(),
                array_merge(
                    ['id' => $resource->getId()],
                    $successRedirectPath->getParams()
                )
            );
        } elseif ($afterEditPath = $brickConfig->getCrudConfig()->getAfterEditPath()) {
            return $this->urlGenerator->generate(
                $afterEditPath->getRoute(),
                array_merge(
                    ['id' => $resource->getId()],
                    $afterEditPath->getParams()
                )
            );
        } elseif ($referer = (string)$this->getRequest()->request->get('referer')) {
            return $referer;
        } else {
            return $this->urlGenerator->generate(
                $brickConfig->getCrudConfig()->getPath(CrudConfigInterface::INDEX)->getRoute()
            );
        }
    }
}
