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
use Lle\CruditBundle\Resolver\RessourceResolver;
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
        RessourceResolver $ressourceResolver,
        RequestStack $requestStack,
        FormFactoryInterface $formFactory,
        BrickResponseCollector $brickResponseCollector,
        UrlGeneratorInterface $urlGenerator
    ) {
        parent::__construct($ressourceResolver, $requestStack);
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
        $ressource = $this->getRessource($brickConfigurator->getDataSource());
        $form = $this->generateForm($brickConfigurator, $ressource);
        $this->bindRequest($form, $brickConfigurator, $ressource);
        /** @var FormConfig $brickConfigurator */
        $view = new BrickView(spl_object_hash($brickConfigurator));
        $view
            ->setTemplate('@LleCrudit/brick/form')
            ->setConfig($brickConfigurator->getConfig())
            ->setData(['form' => $form->createView()]);
        return $view;
    }

    private function bindRequest(FormInterface $form, FormConfig $brickConfig, object $ressource): void
    {
        $form->handleRequest($this->getRequest());
        if ($this->getRequest()->getMethod() === 'POST' and $form->isSubmitted()) {
            if ($form->isValid()) {
                $brickConfig->getDataSource()->save($ressource);
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

    private function generateForm(FormConfig $brickConfigurator, object $ressource): FormInterface
    {
        if ($brickConfigurator->getForm() === null) {
            $formBuilder = $this->formFactory->createBuilder(
                FormType::class,
                $ressource,
                ['allow_extra_fields' => true]
            );
            foreach ($brickConfigurator->getFields() as $field) {
                $formBuilder->add($field->getName(), $field->getType(), $field->getOptions());
            }
            return $formBuilder->getForm();
        } else {
            return $this->formFactory->create($brickConfigurator->getForm(), $ressource);
        }
    }

    private function getRessource(DatasourceInterface $datasource): object
    {
        if ($this->getRequest()->get('id')) {
            $ressource = $datasource->get($this->getRequest()->get('id'));
            if ($ressource === null) {
                throw new CruditException('ressource not found');
            }
            return $ressource;
        } else {
            return $datasource->newInstance();
        }
    }
}
