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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FormFactory extends AbstractBasicBrickFactory
{
    /** @var FormFactoryInterface */
    private $formFactory;

    /** @var BrickResponseCollector  */
    private $brickResponseCollector;

    /** @var UrlGeneratorInterface  */
    private $urlGenerator;

    public function __construct(
        ResourceResolver $resourceResolver,
        RequestStack $requestStack,
        FormFactoryInterface $formFactory,
        BrickResponseCollector $brickResponseCollector,
        UrlGeneratorInterface $urlGenerator
    ) {
        parent::__construct($resourceResolver, $requestStack);
        $this->formFactory = $formFactory;
        $this->brickResponseCollector = $brickResponseCollector;
        $this->urlGenerator = $urlGenerator;
    }

    public function support(BrickConfigInterface $brickConfigurator): bool
    {
        return get_class($brickConfigurator) === FormConfig::class;
    }

    public function buildView(BrickConfigInterface $brickConfigurator): BrickView
    {
        /** @var FormConfig $brickConfigurator */
        $resource = $this->getResource($brickConfigurator->getDataSource());
        $form = $this->generateForm($brickConfigurator, $resource);
        $this->bindRequest($form, $brickConfigurator, $resource);
        /** @var FormConfig $brickConfigurator */
        $view = new BrickView($brickConfigurator);
        $view
            ->setTemplate('@LleCrudit/brick/form')
            ->setConfig($brickConfigurator->getConfig())
            ->setData(['form' => $form->createView()]);
        return $view;
    }

    private function bindRequest(FormInterface $form, FormConfig $brickConfig, object $resource): void
    {
        $form->handleRequest($this->getRequest());
        if ($this->getRequest()->getMethod() === 'POST' and $form->isSubmitted()) {
            if ($form->isValid()) {
                $brickConfig->getDataSource()->save($resource);
                $this->brickResponseCollector->add(
                    new FlashBrickResponse(FlashBrickResponse::SUCCESS, 'crudis.message.success')
                );
                $this->brickResponseCollector->add(new RedirectBrickResponse(
                    $this->urlGenerator->generate(
                        $brickConfig->getCrudConfig()->getPath()->getRoute(),
                        $brickConfig->getCrudConfig()->getPath()->getParams()
                    )
                ));
            } else {
                $this->brickResponseCollector->add(
                    new FlashBrickResponse(FlashBrickResponse::ERROR, 'crudis.message.serror')
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
            return $this->formFactory->create($brickConfigurator->getForm(), $resource);
        }
    }

    private function getResource(DatasourceInterface $datasource): object
    {
        if ($this->getRequest()->get('id')) {
            $resource = $datasource->get($this->getRequest()->get('id'));
            if ($resource === null) {
                throw new CruditException('resource not found');
            }
            return $resource;
        } else {
            return $datasource->newInstance();
        }
    }
}
