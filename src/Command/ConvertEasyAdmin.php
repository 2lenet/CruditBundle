<?php

namespace Lle\CruditBundle\Command;

use Lle\CruditBundle\Service\EasyAdminConverter\Converter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;

class ConvertEasyAdmin extends Command
{
    public const EASYADMIN_PATH = "/config/packages/easy_admin";

    protected static $defaultName = "lle:crudit:convert-easyadmin";

    protected static $defaultDescription = "Convert an EasyAdmin project to a Crudit Project.";

    public function __construct(
        private KernelInterface $kernel,
        private Converter $converter,
    )
    {
        parent::__construct(null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $path = $this->kernel->getProjectDir() . self::EASYADMIN_PATH;

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
}
