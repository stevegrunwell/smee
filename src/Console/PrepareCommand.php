<?php

namespace Smee\Console;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PrepareCommand extends Command
{
    /**
     * Holds the current working directory.
     */
    protected $cwd;

    protected function configure()
    {
        $this->setName('prepare')
            ->setDescription('Prepare a project for Smee')
            ->addOption(
                'base-dir',
                null,
                InputOption::VALUE_OPTIONAL,
                'The path to the root directory of the project',
                getcwd()
            );
    }

    /**
     * Prepare the project directory for effective Smee usage.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $this->cwd = $input->getOption('base-dir');

        $methods = [
            'verifyGitDirectory',
        ];

        foreach ($methods as $method) {
            if ($this->{$method}($io)) {
                return 1;
            }
        }
    }

    /**
     * Verify that a .git directory exists.
     *
     * @param SymfonyStyle $io The input/output element for styling this session.
     *
     * @return int Exit status code — 0 if everything is fine, 1 if something went wrong.
     */
    protected function verifyGitDirectory($io)
    {
        $io->write('Verifying presence of a .git directory...');
        $output = null;
        $code = 0;

        if (is_dir($this->cwd . '/.git')) {
            return $io->writeln('<info>OK</info>');
        }

        $shouldCreate = $io->confirm(sprintf(
            'A .git directory was not found at %s. Would you like to create one?',
            $this->cwd
        ), true);

        if ($shouldCreate) {
            exec('git init', $output, $code);

            if (0 === $code) {
                return $io->success('A .git directory has been created!');
            } else {
                $io->error('Unable to create a .git directory');
                $io->writeLn(implode(PHP_EOL, $output));

                return 1;
            }
        }

        $io->warning('A .git directory is required to use Smee.');
        $io->writeLn('Run `git init` to initialize a repository.');

        return 1;
    }
}
