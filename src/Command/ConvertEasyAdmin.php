<?php

namespace Lle\CruditBundle\Command;

use Lle\CruditBundle\Service\EasyAdminConverter\Converter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(name: 'lle:crudit:convert-easyadmin', description: 'Convert an EasyAdmin project to a Crudit Project.')]
class ConvertEasyAdmin extends Command
{
    public const EASYADMIN_PATH = "/config/packages/easy_admin";

    private KernelInterface $kernel;

    private Converter $converter;

    private Filesystem $filesystem;

    public function __construct(
        KernelInterface $kernel,
        Converter $converter,
        Filesystem $filesystem,
    ) {
        parent::__construct();

        $this->kernel = $kernel;
        $this->converter = $converter;
        $this->filesystem = $filesystem;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $path = $this->kernel->getProjectDir() . self::EASYADMIN_PATH;

        if ($input->getOption("delete")) {
            $this->filesystem->remove(["src/Controller"]);
            $this->filesystem->remove(["src/Crudit"]);
            $this->filesystem->remove(["src/Form"]);
        }

        $config = [];

        $finder = (new Finder())->in($path);

        foreach ($finder as $file) {
            if ($file->isDir() || $file->getExtension() !== "yaml") {
                continue;
            }

            $data = Yaml::parse($file->getContents());
            $config = array_merge_recursive($config, $data);
        }

        $config = $config["easy_admin"];

        foreach ($this->converter->convert($config) as $type => $log) {
            if ($log) {
                $io->{$type}($log);
            }
        }

        return Command::SUCCESS;
    }

    protected function configure()
    {
        $this->addOption(
            "delete",
            "d",
            null,
            "Delete existing Crudit files. Useful if you have to re-run the command.",
        );
    }
}
