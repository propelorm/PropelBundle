<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Command;

use Propel\PropelBundle\Command\AbstractPropelCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * SqlInsertCommand.
 *
 * @author William DURAND <william.durand1@gmail.com>
 */
class SqlInsertCommand extends AbstractPropelCommand
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
The <info>%command.name%</info> command connects to the database and executes all SQL statements found in <comment>app/propel/sql/*schema.sql</comment>.

  <info>php %command.full_name%</info>

The <info>--force</info> parameter has to be used to actually insert SQL.
The <info>--connection</info> parameter allows you to change the connection to use.
The default connection is the active connection (propel.dbal.default_connection).
EOT
            )
            ->setName('propel:sql:insert')
        ;
    }

    /**
     * @see Command
     *
     * @throws \InvalidArgumentException When the target directory does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Bad require but needed :(
        require_once $this->getContainer()->getParameter('propel.path') . '/generator/lib/util/PropelSqlManager.php';

        $this->writeSection($output, '[Propel] You are running the command: propel:sql:insert');

        if ($input->getOption('force')) {
            if ($input->getOption('verbose')) {
                $this->additionalPhingArgs[] = 'verbose';
            }

            $connections = $this->getConnections();
            $sqlDir = $this->getSqlDir();

            $manager = new \PropelSqlManager();
            $manager->setWorkingDirectory($sqlDir);
            $manager->setConnections($connections);

            if ($input->getOption('connection')) {
                list($name, $config) = $this->getConnection($input, $output);
                $this->doSqlInsert($manager, $output, $name);
            } else {
                foreach ($connections as $name => $config) {
                    $output->writeln(sprintf('Use connection named <comment>%s</comment> in <comment>%s</comment> environment.',
                        $name, $this->getApplication()->getKernel()->getEnvironment()));
                    $this->doSqlInsert($manager, $output, $name);
                }
            }
        } else {
            $output->writeln('<error>You have to use --force to execute all SQL statements.</error>');
        }
    }

    protected function getSqlDir()
    {
        return sprintf('%s/propel/sql', $this->getApplication()->getKernel()->getRootDir());
    }

    /**
     * @param \PropelSqlManager $manager
     * @param OutputInterface $output
     * @param string $connectionName
     */
    protected function doSqlInsert(\PropelSqlManager $manager, OutputInterface $output, $connectionName)
    {
        try {
            $statusCode = $manager->insertSql($connectionName);
        } catch (\Exception $e) {
            return $this->writeSection(
                $output,
                array('[Propel] Exception', '', $e),
                'fg=white;bg=red'
            );
        }

        if (true === $statusCode) {
            $this->writeSection(
                $output,
                '<info>All SQL statements have been generated.</info>',
                'bg=black'
            );
        } else {
            $this->writeSection(
                $output,
                '<comment>No SQL statements found.</comment>',
                'bg=black'
            );
        }
    }

    /**
     * @return array
     */
    protected function getConnections()
    {
        $propelConfiguration = $this->getContainer()->get('propel.configuration');

        $connections = array();
        foreach ($propelConfiguration['datasources'] as $name => $config) {
            $connections[$name] = $config['connection'];
        }

        return $connections;
    }
}
