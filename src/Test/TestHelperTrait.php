<?php

namespace Lle\CruditBundle\Test;

use App\Repository\UserRepository;
use Lle\CruditBundle\Dto\Layout\LinkElement;
use Lle\CruditBundle\Registry\MenuRegistry;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Routing\RouterInterface;

trait TestHelperTrait
{
    public function testMenuNav(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        $menuRegistry = $container->get(MenuRegistry::class);
        $router = $container->get(RouterInterface::class);
        $userRepository = $container->get(self::USER_REPOSITORY);

        $client->loginUser($userRepository->findOneByEmail(self::LOGIN_USER));

        foreach ($menuRegistry->getElements('') as $elem) {
            if ($elem instanceof LinkElement && $elem->getPath() != null) {
                $this->checkRoute($router, $elem, $client);
            }

            foreach ($elem->getChildren() as $child) {
                if ($child instanceof LinkElement && $child->getPath() != null) {
                    $this->checkRoute($router, $child, $client);
                }
            }
        }
    }

    protected function checkRoute(?object $router, LinkElement $elem, KernelBrowser $client): void
    {
        if (in_array($elem->getPath()->getRoute(), self::EXCLUDED_ROUTES)) {
            return;
        }

        $url = $router->generate($elem->getPath()->getRoute(), $elem->getPath()->getParams());
        $client->request('GET', $url);

        $code = $client->getResponse()->getStatusCode();
        if ($code != '200') {
            dump($client->getResponse()->getContent());
        }

        $this->assertEquals(
            '200',
            $code,
            'Erreur ' . $elem->getPath()->getRoute()
        );

        $content = $client->getResponse()->getContent();
        $crawler = new Crawler($content);

        $editElements = $crawler->filter('span.btn-wrapper > a > i.fa-edit');
        $this->checkAction($editElements, $client);

        $showElements = $crawler->filter('span.btn-wrapper > a > i.fa-search');
        $this->checkAction($showElements, $client);
    }

    protected function checkAction(Crawler $elements, KernelBrowser $client): void
    {
        foreach ($elements as $element) {
            foreach ($element->parentNode->attributes as $attribute) {
                if ($attribute->nodeName === 'href') {
                    $client->request('GET', $attribute->value);
                    $code = $client->getResponse()->getStatusCode();

                    if ($code != '200') {
                        dump($client->getResponse()->getStatusCode());
                    }

                    $this->assertEquals(
                        '200',
                        $code,
                        'Erreur ' . $attribute->value,
                    );

                    break 2;
                }
            }
        }
    }
}