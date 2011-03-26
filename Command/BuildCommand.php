<?php

namespace Propel\PropelBundle\Command;

use Propel\PropelBundle\Command\PhingCommand;
use Propel\PropelBundle\Command\BuildModelCommand;
use Propel\PropelBundle\Command\BuildSqlCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * BuildCommand.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author William DURAND <william.durand1@gmail.com>
 */
class BuildCommand extends PhingCommand
{
    protected $additionalPhingArgs = array();

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDescription('Hub for Propel build commands (model, sql)')
            ->setDefinition(array(
                new InputOption('--classes', '', InputOption::VALUE_NONE, 'Build only classes'),
                new InputOption('--sql', '', InputOption::VALUE_NONE, 'Build only code'),
                new InputOption('--insert-sql', '', InputOption::VALUE_NONE, 'Build all and insert SQL'),
            ))
            ->setName('propel:build');
    }

    /**
     * @see Command
     *
     * @throws \InvalidArgumentException When the target directory does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('sql')) {
            $output->writeln('<info>Building model classes</info>');
            $modelCommand = new BuildModelCommand();
            $modelCommand->setApplication($this->getApplication());
            $modelCommand->execute($input, $output);
        }

        if (!$input->getOption('classes')) {
            $output->writeln('<info>Building model sql</info>');
            $sqlCommand = new BuildSQLCommand();
            $sqlCommand->setApplication($this->getApplication());
            $sqlCommand->execute($input, $output);
        }

        if ($input->getOption('insert-sql')) {
            $output->writeln('<info>Inserting SQL statements</info>');
            $insertCommand = new InsertSqlCommand();
            $insertCommand->setApplication($this->getApplication());

            // By-pass the '--force' required option
            $this->addOption('--force', '', InputOption::VALUE_NONE, '');
            $input->setOption('force', true);

            $insertCommand->execute($input, $output);
        }
    }
}
