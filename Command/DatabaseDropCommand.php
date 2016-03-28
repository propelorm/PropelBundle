<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Command;

use Propel\Runtime\Propel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * DatabaseDropCommand class.
 * Useful to drop a database.
 *
 * @author William DURAND
 */
class DatabaseDropCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('propel:database:drop')
            ->setDescription('Drop a given database or the default one.')
            ->setHelp(<<<EOT
The <info>propel:database:drop</info> command will drop your database.

  <info>php app/console propel:database:drop</info>

The <info>--force</info> parameter has to be used to actually drop the database.
The <info>--connection</info> parameter allows you to change the connection to use.
The default connection is the active connection (propel.dbal.default_connection).
EOT
            )

            ->addOption('connection',   null, InputOption::VALUE_OPTIONAL, 'Connection to use. Example: default, bookstore')
            ->addOption('force',        null, InputOption::VALUE_NONE, 'Set this parameter to execute this action.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('force')) {
            $output->writeln('<error>You have to use the "--force" option to drop the database.</error>');

            return;
        }

        if ('prod' === $this->getApplication()->getKernel()->getEnvironment()) {
            $this->writeSection($output, 'WARNING: you are about to drop a database in production !', 'bg=red;fg=white');

            if (false === $this->askConfirmation($input, $output, 'Are you sure ? (y/n) ', false)) {
                $output->writeln('Aborted, nice decision !');

                return -2;
            }
        }

        $connectionName = $input->getOption('connection') ?: $this->getDefaultConnection();
        $config = $this->getConnectionData($connectionName);
        $connection = Propel::getConnection($connectionName);
        $dbName = $this->parseDbName($config['dsn']);

        if (null === $dbName) {
            return $output->writeln('<error>No database name found.</error>');
        } else {
            $query  = 'DROP DATABASE '. $dbName .';';
        }

        try {
            $statement = $connection->prepare($query);
            $statement->execute();

            $output->writeln(sprintf('<info>Database <comment>%s</comment> has been dropped.</info>', $dbName));
        } catch (\Exception $e) {
            $this->writeSection($output, array(
                '[Propel] Exception caught',
                '',
                $e->getMessage()
            ), 'fg=white;bg=red');
        }
    }
}
