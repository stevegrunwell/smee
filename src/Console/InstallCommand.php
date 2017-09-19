<?php

namespace Smee\Console;

use Exception;
use Smee\Project;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InstallCommand extends Command
{
    protected function configure()
    {
        $this->setName('smee:install')
            ->setDescription('Install git hooks for the current project.')
            ->addOption(
                'base-dir',
                null,
                InputOption::VALUE_OPTIONAL,
                'The path to the root directory of the project',
                getcwd()
            )
            ->addOption(
                'hooks',
                null,
                InputOption::VALUE_OPTIONAL,
                'The path to the hooks directory, relative to the project root',
                '.githooks'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $project = new Project($input->getOption('base-dir'), $input->getOption('hooks'));
        $io = new SymfonyStyle($input, $output);

        try {
            $copied = $project->copyHooks();
        } catch (Exception $e) {
            $io->getErrorStyle()->error(sprintf('An error occurred while copying git hooks: %s', $e->getMessage()));
            return 1;
        }

        $io->section('Copied git hooks:');
        $io->listing($copied);
    }
}
