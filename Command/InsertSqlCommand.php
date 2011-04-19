<?php

namespace Propel\PropelBundle\Command;

use Propel\PropelBundle\Command\PhingCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
            ->addOption('connection', null, InputOption::VALUE_OPTIONAL, 'Set this parameter to define a connection to use')
            ->setHelp(<<<EOT
The <info>propel:insert-sql</info> command connects to the database and executes all SQL statements found in <comment>app/propel/sql/*schema.sql</comment>.

  <info>php app/console propel:insert-sql</info>

The <info>--force</info> parameter has to be used to actually insert SQL.
The <info>--connection</info> parameter allows you to change the connection to use.
The default connection is the active connection (propel.dbal.default_connection).
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
            list($name, $defaultConfig) = $this->getConnection($input, $output);

            $this->callPhing('insert-sql', array(
                'propel.database.url'       => $defaultConfig['connection']['dsn'],
                'propel.database.database'  => $defaultConfig['adapter'],
                'propel.database.user'      => $defaultConfig['connection']['user'],
                'propel.database.password'  => $defaultConfig['connection']['password'],
                'propel.schema.dir'         => $this->getApplication()->getKernel()->getRootDir() . '/propel/schema/',
            ));

            $output->writeln('<info>All SQL statements have been executed.</info>');
        } else {
            $output->writeln('<error>You have to use --force to execute all SQL statements.</error>');
        }
    }
}
