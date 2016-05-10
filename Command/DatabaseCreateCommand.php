<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Command;

use Propel\Runtime\Connection\ConnectionManagerSingle;
use Propel\Runtime\Propel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * DatabaseCreateCommand class.
 * Useful to create a database.
 *
 * @author William DURAND
 */
class DatabaseCreateCommand extends AbstractCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('propel:database:create')
            ->setDescription('Create a given database or the default one.')

            ->addOption('connection', null, InputOption::VALUE_OPTIONAL, 'Set this parameter to define a connection to use')
        ;
    }

    /**
     * @see Command
     *
     * @throws \InvalidArgumentException When the target directory does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $connectionName = $input->getOption('connection') ?: $this->getDefaultConnection();
        $config = $this->getConnectionData($connectionName);
        $dbName = $this->parseDbName($config['dsn']);

        if (null === $dbName) {
            return $output->writeln('<error>No database name found.</error>');
        } else {
            $query  = 'CREATE DATABASE '. $dbName .';';
        }

        try {
            $manager = new ConnectionManagerSingle();
            $manager->setConfiguration($this->getTemporaryConfiguration($config));

            $serviceContainer = Propel::getServiceContainer();
            $serviceContainer->setAdapterClass($connectionName, $config['adapter']);
            $serviceContainer->setConnectionManager($connectionName, $manager);

            $connection = Propel::getConnection($connectionName);

            $statement = $connection->prepare($query);
            $statement->execute();

            $output->writeln(sprintf('<info>Database <comment>%s</comment> has been created.</info>', $dbName));
        } catch (\Exception $e) {
            $this->writeSection($output, array(
                '[Propel] Exception caught',
                '',
                $e->getMessage()
            ), 'fg=white;bg=red');
        }
    }

    /**
     * Create a temporary configuration to connect to the database in order
     * to create a given database. This idea comes from Doctrine1.
     *
     * @see https://github.com/doctrine/doctrine1/blob/master/lib/Doctrine/Connection.php#L1491
     *
     * @param  array $config A Propel connection configuration.
     * @return array
     */
    private function getTemporaryConfiguration($config)
    {
        $dbName = $this->parseDbName($config['dsn']);

        $config['dsn'] = preg_replace(
            '#;?dbname='.$dbName.';?#',
            '',
            $config['dsn']
        );

        return $config;
    }
}
