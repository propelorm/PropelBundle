<?php

namespace Propel\PropelBundle\Command;

use Propel\PropelBundle\Command\PhingCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * DatabaseDropCommand class.
 * Useful to drop a database.
 *
 * @author William DURAND
 */
class DatabaseDropCommand extends PhingCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDescription('Drop a given database or the default one.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action.')
            ->addOption('connection', null, InputOption::VALUE_OPTIONAL, 'Set this parameter to define a connection to use')
            ->setHelp(<<<EOT
The <info>propel:database:drop</info> command will drop your database.</comment>.

  <info>php app/console propel:insert-sql</info>

The <info>--force</info> parameter has to be used to actually drop the database.
The <info>--connection</info> parameter allows you to change the connection to use.
The default connection is the active connection (propel.dbal.default_connection).
EOT
            )
            ->setName('propel:database:drop');
    }

    /**
     * @see Command
     *
     * @throws \InvalidArgumentException When the target directory does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('force')) {
            list($name, $config) = $this->getConnection($input, $output);
            $dbName = $this->parseDbName($config['connection']['dsn']);
            $query  = 'DROP DATABASE '. $dbName .';';

            try {
                $connection = \Propel::getConnection($name);
                $statement  = $connection->prepare($query);
                //$statement->execute();
                $output->writeln(sprintf('<info><comment>%s</comment> has been dropped.</info>', $dbName));
            } catch (Exception $e) {
                $output->writeln(sprintf('<error>An error has occured: %s</error>', $e->getMessage()));
            }
        } else {
            $output->writeln('<error>You have to use --force to drop the database.</error>');
        }
    }
}
