<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class AclInitCommand extends SqlInsertCommand
{
    protected function configure()
    {
        $this
            ->setDescription('Initialize "Access Control Lists" model and SQL')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action.')
            ->addOption('connection', null, InputOption::VALUE_OPTIONAL, 'Set this parameter to define a connection to use')
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
        // Generate ACL model
        if (true == $result = $this->callPhing('om')) {
            $output->writeln(sprintf(
                '>>  <info>%20s</info>    Generated model classes from <comment>%s</comment>',
                $this->getApplication()->getKernel()->getBundle('PropelBundle')->getName(),
                'acl_schema.xml'
            ));
        } else {
            $this->writeTaskError($output, 'om');

            return 1;
        }

        // Prepare SQL directory
        $sqlDirectory = $this->getSqlDir();
        $filesystem   = new Filesystem();
        $filesystem->remove($sqlDirectory);
        $filesystem->mkdir($sqlDirectory);

        if (true == $result = $this->callPhing('build-sql', array('propel.sql.dir' => $sqlDirectory))) {
            $this->writeSection(
                $output,
                '<comment>1</comment> <info>SQL file has been generated.</info>'
            );
        } else {
            $this->writeTaskError($output, 'build-sql');

            return 2;
        }

        return parent::execute($input, $output);
    }

    protected function getFinalSchemas(KernelInterface $kernel, BundleInterface $bundle = null)
    {
        $aclSchema = new \SplFileInfo($kernel->locateResource('@PropelBundle/Resources/acl_schema.xml'));

        return array((string) $aclSchema => array($kernel->getBundle('PropelBundle'), $aclSchema));
    }

    protected function getSqlDir()
    {
        return sprintf('%s/cache/%s/propel/acl/sql',
            $this->getApplication()->getKernel()->getRootDir(),
            $this->getApplication()->getKernel()->getEnvironment()
        );
    }
}
