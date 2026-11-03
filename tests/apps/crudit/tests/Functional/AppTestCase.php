<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Client;
use App\Enum\Priority;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * A browser on the real application (real bundle, real container, SQLite) and a database of 3 clients, rebuilt for
 * each test: Alice and Bob active (not deletable: Client::canDelete()), Chloé inactive.
 */
abstract class AppTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $tool = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $tool->dropSchema($metadata);
        $tool->createSchema($metadata);
        $clients = [];
        foreach (['Alice', 'Bob', 'Chloé'] as $name) {
            $em->persist($clients[$name] = (new Client())->setName($name)->setEmail(strtolower($name) . '@example.org')->setActive('Chloé' !== $name));
        }
        // The fields of lot 3.4.a: all filled for Alice, some for Bob, none for Chloé (empty values)
        $clients['Alice']
            ->setPhone('+33612345678')
            ->setWebsite('HTTPS://WWW.alice.example/shop')
            ->setAmount('1234.50')
            ->setProgress(45)
            ->setBudgetUsed(130)
            ->setColor('#0077a8')
            ->setNotes("# Notes\n\nA **good** client.<script>alert(1)</script>\n\n" . str_repeat('A long line of notes. ', 20))
            ->setStatus('customer')
            ->setBlocked(true)
            ->setTags(['vip', 'north'])
            ->setReferrer($clients['Bob'])
            ->setPriority(Priority::High)
            ->setChannels(['email', 'phone']);
        $clients['Bob']
            ->setWebsite('javascript:alert(1)')
            ->setAmount('20')
            ->setProgress(100)
            ->setStatus('prospect')
            ->setBlocked(false)
            ->setTags(['since' => 2024, 'source' => 'fair', 'visits' => []]);
        $em->flush();
    }
}
