<?php

namespace Lle\CruditBundle\Test;

use Lle\CruditBundle\Dto\Layout\LinkElement;
use Lle\CruditBundle\Registry\MenuRegistry;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Stopwatch\Stopwatch;

trait TestHelperTrait
{
    protected KernelBrowser $client;
    protected RouterInterface $router;
    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::buildClient();
        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $this->router = $router;
        $this->stopwatch = new Stopwatch();

        $this->loginUser();
    }

    public function testMenuNav(): void
    {
        $menuRegistry = static::getContainer()->get(MenuRegistry::class);

        foreach ($menuRegistry->getElements('') as $element) {
            if ($element instanceof LinkElement && $element->getPath() !== null) {
                $this->checkRoute($element);
            }

            foreach ($element->getChildren() as $child) {
                if ($child instanceof LinkElement && $child->getPath() !== null) {
                    $this->checkRoute($child);
                }
            }
        }
    }

    protected function buildClient(): KernelBrowser
    {
        self::ensureKernelShutdown();

        return static::createClient();
    }

    protected function loginUser(): void
    {
        $userRepository = static::getContainer()->get(self::USER_REPOSITORY);

        $this->client->loginUser($userRepository->findOneByEmail(self::LOGIN_USER));
    }

    protected function checkRoute(LinkElement $element): void
    {
        if (in_array($element->getPath()->getRoute(), self::EXCLUDED_ROUTES)) {
            return;
        }

        $url = $this->router->generate($element->getPath()->getRoute(), $element->getPath()->getParams());
        $this->stopwatch->start($url);
        $this->client->request('GET', $url);
        $this->stopwatch->stop($url);
        $e = $this->stopwatch->getEvent($url); // dumps e.g. '4.50 MiB - 26 ms'
        if ($e->getDuration() > 500) {
            $this->addWarning("page > 500ms " . $url . " " . $e->getDuration() . "ms");
        }
        $code = $this->client->getResponse()->getStatusCode();
        if ($code != '200') {
            echo($this->client->getResponse()->getStatusCode());
        }
        self::assertEquals(
            '200',
            $code,
            'Erreur ' . $element->getPath()->getRoute() . ' : ' . $this->getPageTitle(),
        );

        $content = $this->client->getResponse()->getContent();
        $crawler = new Crawler($content);

        $editElements = $crawler->filter('span.btn-wrapper > a > i.fa-edit');
        $this->checkAction($editElements);

        $showElements = $crawler->filter('span.btn-wrapper > a > i.fa-search');
        $this->checkAction($showElements);

        $addElements = $crawler->filter('span.btn-wrapper > a > i.fa-plus');
        $this->checkAction($addElements);
    }

    protected function checkAction(Crawler $elements): void
    {
        foreach ($elements as $element) {
            foreach ($element->parentNode->attributes as $attribute) {
                if ($attribute->nodeName === 'href') {
                    $this->stopwatch->start($attribute->value);
                    $this->client->request('GET', $attribute->value);
                    $this->stopwatch->stop($attribute->value);
                    $e = $this->stopwatch->getEvent($attribute->value); // dumps e.g. '4.50 MiB - 26 ms'
                    if ($e->getDuration() > 500) {
                        $this->addWarning("page > 500ms " . $attribute->value . " " . $e->getDuration() . "ms");
                    }
                    $code = $this->client->getResponse()->getStatusCode();

                    if ($code != '200') {
                        echo($this->client->getResponse()->getStatusCode());
                    }

                    self::assertEquals(
                        '200',
                        $code,
                        'Erreur ' . $attribute->value . ' : ' . $this->getPageTitle(),
                    );

                    break 2;
                }
            }
        }
    }

    protected function getPageTitle(): string
    {
        $titleElements = $this->client->getCrawler()->filter('title');
        if ($titleElements->count() > 0) {
            return $titleElements->first()->text();
        }

        return '';
    }
}
