<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Command;

use Propel\PropelBundle\Command\PhingCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * DatabaseCreateCommand class.
 * Useful to create a database.
 *
 * @author William DURAND
 */
class DatabaseCreateCommand extends PhingCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDescription('Create a given database or the default one.')
            ->addOption('connection', null, InputOption::VALUE_OPTIONAL, 'Set this parameter to define a connection to use')
            ->setName('propel:database:create');
    }

    /**
     * @see Command
     *
     * @throws \InvalidArgumentException When the target directory does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->writeSection($output, '[Propel] You are running the command: propel:database:create');

        list($name, $config) = $this->getConnection($input, $output);
        $dbName = $this->parseDbName($config['connection']['dsn']);
        $query  = 'CREATE DATABASE '. $dbName .';';

        try {
            \Propel::setConfiguration($this->getTemporaryConfiguration($name, $config));
            $connection = \Propel::getConnection($name);

            $statement = $connection->prepare($query);
            $statement->execute();

            $output->writeln(sprintf('<info>[Propel] <comment>%s</comment> has been created.</info>', $dbName));
        } catch (\Exception $e) {
            $this->writeSection($output, array('[Propel] Exception catched', '', $e->getMessage()), 'fg=white;bg=red');
        }
    }

    /**
     * Create a temporary configuration to connect to the database in order
     * to create a given database. This idea comes from Doctrine1.
     *
     * @see https://github.com/doctrine/doctrine1/blob/master/lib/Doctrine/Connection.php#L1491
     *
     * @param string $name      A connection name.
     * @param array $config     A Propel connection configuration.
     * @return array
     */
    private function getTemporaryConfiguration($name, $config)
    {
        $dbName = $this->parseDbName($config['connection']['dsn']);

        $config['connection']['dsn'] = preg_replace(
            '#dbname='.$dbName.'#',
            'dbname='.strtolower($config['adapter']),
            $config['connection']['dsn']
        );

        return array(
            'datasources' => array($name => $config)
        );
    }
}
