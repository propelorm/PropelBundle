<?php

namespace Propel\PropelBundle\Command;

use Propel\PropelBundle\Command\PhingCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * InsertSqlCommand.
 *
 * @author William DURAND <william.durand1@gmail.com> 
 */
class InsertSqlCommand extends PhingCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDescription('Insert SQL for current model')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action.')
            ->setHelp(<<<EOT
The <info>propel:insert-sql</info> command connects to the database and executes all SQL statements found in <comment>app/propel/sql/*schema.sql</comment>.

  <info>php app/console propel:insert-sql</info>

  The <info>--force</info> parameter has to be used to actually insert SQL.

EOT
            )
            ->setName('propel:insert-sql')
        ;
    }

    /**
     * @see Command
     *
     * @throws \InvalidArgumentException When the target directory does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('force')) {
            $this->callPhing('insert-sql');
        } else {
            $output->writeln('<error>You have to use --force to execute all SQL statements.</error>');
        }
    }
}
