<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Client;
use App\Entity\Intervention;
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

        foreach (range(1, 25) as $i) {
            $this->em->persist((new Client())
                ->setName(sprintf('Client %02d', $i))
                ->setEmail(sprintf('client%02d@example.org', $i))
                ->setActive(0 !== $i % 4)
                ->setCreatedAt(new \DateTime(sprintf('2026-01-%02d', $i))));
        }
        // Interventions in each state of their workflow (lot 3.4.c); INT-01 has no technician: it cannot be planned
        foreach (['to_plan', 'to_plan', 'planned', 'in_progress', 'done', 'cancelled'] as $i => $status) {
            $this->em->persist((new Intervention())
                ->setReference(sprintf('INT-%02d', $i + 1))
                ->setTechnician(0 === $i ? null : 'Camille Martin')
                ->setStatus($status));
        }
        $this->em->flush();
        $output->writeln('25 clients, 6 interventions.');

        return Command::SUCCESS;
    }
}
