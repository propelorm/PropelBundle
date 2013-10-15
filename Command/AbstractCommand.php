<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Command;

use Propel\Generator\Command\AbstractCommand as BaseCommand;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
abstract class AbstractCommand extends ContainerAwareCommand
{
    /**
     * @var string
     */
    protected $cacheDir = null;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->addOption('platform',  null, InputOption::VALUE_REQUIRED,  'The platform', BaseCommand::DEFAULT_PLATFORM)
        ;
    }

    protected function runCommand(Command $command, array $parameters, InputInterface $input, OutputInterface $output)
    {
        array_unshift($parameters, $this->getName());
        $parameters = array_merge(array(
            '--input-dir'   => $this->cacheDir,
            '--verbose'     => $input->getOption('verbose'),
        ), $parameters);

        if ($input->hasOption('platform')) {
            $parameters['--platform'] = $input->getOption('platform');
        }

        var_dump($parameters);

        $commandInput = new ArrayInput($parameters);

        $command->setApplication($this->getApplication());

        return $command->run($commandInput, $output);
    }

    protected function setupBuildTimeFiles()
    {
        $kernel = $this->getApplication()->getKernel();
        $this->cacheDir = $kernel->getCacheDir().'/propel';

        $fs = new Filesystem();
        $fs->mkdir($this->cacheDir);

        // collect all schemas
        //$this->copySchemas($kernel, $this->cacheDir);

        // build.properties
        $this->createBuildPropertiesFile($kernel, $this->cacheDir.'/build.properties');

        // buildtime-conf.xml
        $this->createBuildTimeFile($this->cacheDir.'/buildtime-conf.xml');
    }

    protected function getConnections(array $connections)
    {
        $knownConnections = $this->getContainer()->getParameter('propel.configuration');

        $dsnList = array();
        foreach ($connections as $connection) {
            if (!isset($knownConnections[$connection])) {
                throw new \InvalidArgumentException(sprintf('Unknown connection "%s"', $connection));
            }

            $dsnList[] = $this->buildDsn($connection, $knownConnections[$connection]['connection']);
        }

        return $dsnList;
    }

    protected function buildDsn($connectionName, array $connectionData)
    {
        return sprintf('%s=%s;user=%s;password=%s', $connectionName, $connectionData['dsn'], $connectionData['user'], $connectionData['password']);
    }

    /**
     * Create a 'build.properties' file.
     *
     * @param KernelInterface $kernel The application kernel.
     * @param string          $file   Should be 'build.properties'.
     */
    protected function createBuildPropertiesFile(KernelInterface $kernel, $file)
    {
        $fs = new Filesystem();
        $buildPropertiesFile = $kernel->getRootDir().'/config/propel.ini';

        if ($fs->exists($file)) {
            $fs->copy($buildPropertiesFile, $file);
        } else {
            $fs->touch($file);
        }
    }

    /**
     * Create an XML file which represents propel.configuration
     *
     * @param string $file Should be 'buildtime-conf.xml'.
     */
    protected function createBuildTimeFile($file)
    {
        if (!$this->getContainer()->hasParameter('propel.configuration')) {
            throw new \InvalidArgumentException('Could not find Propel configuration.');
        }

        $xml = strtr(<<<EOT
<?xml version="1.0"?>
<config>
<propel>
<datasources default="%default_connection%">

EOT
        , array('%default_connection%' => $this->getContainer()->getParameter('propel.dbal.default_connection')));

        $datasources = $this->getContainer()->getParameter('propel.configuration');
        foreach ($datasources as $name => $datasource) {
            $xml .= strtr(<<<EOT
<datasource id="%name%">
<adapter>%adapter%</adapter>
<connection>
<dsn>%dsn%</dsn>
<user>%username%</user>
<password>%password%</password>
</connection>
</datasource>

EOT
            , array(
                '%name%' => $name,
                '%adapter%' => $datasource['adapter'],
                '%dsn%' => $datasource['connection']['dsn'],
                '%username%' => $datasource['connection']['user'],
                '%password%' => isset($datasource['connection']['password']) ? $datasource['connection']['password'] : '',
            ));
        }

        $xml .= <<<EOT
</datasources>
</propel>
</config>
EOT;

        file_put_contents($file, $xml);
    }
}
