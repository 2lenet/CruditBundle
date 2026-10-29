<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:seed', description: 'Creates the schema and a few clients (the test application has no migration)')]
class SeedCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $tool->dropSchema($metadata);
        $tool->createSchema($metadata);

        $statuses = ['prospect', 'customer', 'lost'];
        foreach (range(1, 25) as $i) {
            $client = (new Client())
                ->setName(sprintf('Client %02d', $i))
                ->setEmail(sprintf('client%02d@example.org', $i))
                ->setActive(0 !== $i % 4)
                ->setCreatedAt(new \DateTime(sprintf('2026-01-%02d', $i)));
            // The fields of lot 3.4.a; every fifth client has none (empty values)
            if (0 !== $i % 5) {
                $client
                    ->setPhone(sprintf('+336123456%02d', $i))
                    ->setWebsite(sprintf('https://www.client%02d.example/', $i))
                    ->setAmount(sprintf('%d.%02d', $i * 137, $i))
                    ->setProgress(($i * 9) % 121)
                    ->setBudgetUsed($i * 7)
                    ->setColor(sprintf('#%02x77a8', $i * 9))
                    ->setNotes(sprintf("**Client %02d**: a note long enough to be clamped on two lines in the table, and shown whole in its card and on its record.", $i))
                    ->setStatus($statuses[$i % 3])
                    ->setBlocked(0 === $i % 3)
                    ->setTags(['vip', sprintf('zone %d', $i % 4)]);
            }
            $this->em->persist($client);
        }
        $this->em->flush();
        $output->writeln('25 clients.');

        return Command::SUCCESS;
    }
}
