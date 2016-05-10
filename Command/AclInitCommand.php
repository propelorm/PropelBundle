<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class AclInitCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setDescription('Initialize "Access Control Lists" model and SQL')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action.')
            ->addOption('connection', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Connection to use. Example: default, bookstore')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command connects to the database and executes all SQL statements required to setup the ACL database, it also generates the ACL model.

  <info>php %command.full_name%</info>

The <info>--force</info> parameter has to be used to actually insert SQL.
The <info>--connection</info> parameter allows you to change the connection to use.
The default connection is the active connection (propel.dbal.default_connection).
EOT
        )
            ->setName('propel:acl:init')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $outputDir = realpath($this->getApplication()->getKernel()->getRootDir().'/../');

        // Generate ACL model
        $modelBuildCmd = new \Propel\Generator\Command\ModelBuildCommand();
        $modelBuildArgs = array(
            '--output-dir' => $outputDir,
        );

        if ($this->runCommand($modelBuildCmd, $modelBuildArgs, $input, $output) === 0) {
            $output->writeln(sprintf(
                '>>  <info>%20s</info>    Generated model classes from <comment>%s</comment>',
                $this->getApplication()->getKernel()->getBundle('PropelBundle')->getName(),
                'acl_schema.xml'
            ));
        } else {
            $this->writeTaskError($output, 'model:build');

            return 1;
        }

        // Prepare SQL
        $sqlBuildCmd = new \Propel\Generator\Command\SqlBuildCommand();
        $sqlBuildArgs = array(
            '--connection' => $this->getConnections($input->getOption('connection')),
            '--output-dir'  => $this->getCacheDir(),
        );

        if ($this->runCommand($sqlBuildCmd, $sqlBuildArgs, $input, $output) === 0) {
            $this->writeSection(
                $output,
                '<comment>1</comment> <info>SQL file has been generated.</info>'
            );
        } else {
            $this->writeTaskError($output, 'sql:build');

            return 2;
        }

        
        if ($input->getOption('force')) {
            // insert sql
            $sqlInsertCmd = new \Propel\Generator\Command\SqlInsertCommand();
            $sqlInsertArgs = array(
                '--connection' => $this->getConnections($input->getOption('connection')),
                '--sql-dir' => $this->getCacheDir(),
            );

            if ($this->runCommand($sqlInsertCmd, $sqlInsertArgs, $input, $output) === 0) {
                $this->writeSection(
                    $output,
                    '<comment>1</comment> <info>SQL file has been inserted.</info>'
                );
            } else {
                $this->writeTaskError($output, 'sql:insert');

                return 3;
            }
        }

        return 0;
    }

    /**
     * {@inheritdoc}
     *
     * @note We override this method to only return the acl-related schema
     */
    protected function getFinalSchemas(KernelInterface $kernel, BundleInterface $bundle = null)
    {
        $aclSchema = new \SplFileInfo($kernel->locateResource('@PropelBundle/Resources/acl_schema.xml'));

        return array(
            array($kernel->getBundle('PropelBundle'), $aclSchema)
        );
    }

    /**
     * {@inheritdoc}
     *
     * @note We override this method to modify the cache directory
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->cacheDir = $this->cacheDir . '/acl';

        $this->setupBuildTimeFiles();
    }
}
