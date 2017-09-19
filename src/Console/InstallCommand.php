<?php

namespace Smee\Console;

use Exception;
use Smee\Exceptions\HookExistsException;
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

        return $this->executeCommand($project, $io);
    }

    /**
     * Method used for running execute(), even if an exception is thrown along the way.
     *
     * @param Project      $project
     * @param SymfonyStyle $io
     */
    protected function executeCommand($project, $io)
    {
        try {
            $project->copyHooks();
        } catch (Exception $e) {
            $io->getErrorStyle()->error(sprintf('An error occurred while copying git hooks: %s', $e->getMessage()));
            return 1;
        }

        $copied = $project->getCopiedHooks();

        // Nothing was copied, return early.
        if (empty($copied)) {
            return;
        }

        $io->section('Copied git hooks:');
        $io->listing($project->getCopiedHooks());
    }
}
