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
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
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
     * @var Symfony\Component\HttpKernel\Bundle\BundleInterface
     */
    protected $bundle = null;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->addOption('platform',  null, InputOption::VALUE_REQUIRED,  'The platform', BaseCommand::DEFAULT_PLATFORM)
        ;
    }

    /**
     * @return \Symfony\Component\Console\Command\Command
     */
    protected abstract function createSubCommandInstance();

    /**
     * @return array
     */
    protected abstract function getSubCommandArguments(InputInterface $input);

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupBuildTimeFiles();

        $params = $this->getSubCommandArguments($input);
        $command = $this->createSubCommandInstance();

        return $this->runCommand($command, $params, $input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->checkConfiguration();

        if ($input->hasArgument('bundle') && '@' === substr($input->getArgument('bundle'), 0, 1)) {
            $this->bundle = $this
                ->getContainer()
                ->get('kernel')
                ->getBundle(substr($input->getArgument('bundle'), 1));
        }
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
        $this->copySchemas($kernel, $this->cacheDir);

        // build.properties
        $this->createBuildPropertiesFile($kernel, $this->cacheDir.'/build.properties');

        // buildtime-conf.xml
        $this->createBuildTimeFile($this->cacheDir.'/buildtime-conf.xml');
    }

    /**
     * @param KernelInterface $kernel The application kernel.
     */
    protected function copySchemas(KernelInterface $kernel, $cacheDir)
    {
        $filesystem = new Filesystem();

        if (!is_dir($cacheDir)) {
            $filesystem->mkdir($cacheDir);
        }

        $base = ltrim(realpath($kernel->getRootDir().'/..'), DIRECTORY_SEPARATOR);

        $finalSchemas = $this->getFinalSchemas($kernel, $this->bundle);
        foreach ($finalSchemas as $schema) {
            list($bundle, $finalSchema) = $schema;
            $packagePrefix = $this->getPackagePrefix($bundle, $base);

            $tempSchema = $bundle->getName().'-'.$finalSchema->getBaseName();
            $this->tempSchemas[$tempSchema] = array(
                'bundle'    => $bundle->getName(),
                'basename'  => $finalSchema->getBaseName(),
                'path'      => $finalSchema->getPathname(),
            );

            $file = $cacheDir.DIRECTORY_SEPARATOR.$tempSchema;
            $filesystem->copy((string) $finalSchema, $file, true);

            // the package needs to be set absolute
            // besides, the automated namespace to package conversion has
            // not taken place yet so it needs to be done manually
            $database = simplexml_load_file($file);

            if (isset($database['package'])) {
                // Do not use the prefix!
                // This is used to override the package resulting from namespace conversion.
                $database['package'] = $database['package'];
            } elseif (isset($database['namespace'])) {
                $database['package'] = $packagePrefix . str_replace('\\', '.', $database['namespace']);
            } else {
                throw new \RuntimeException(
                    sprintf('%s : Please define a `package` attribute or a `namespace` attribute for schema `%s`',
                        $bundle->getName(), $finalSchema->getBaseName())
                );
            }

            // @todo
            //if ($this->input && $this->input->hasOption('connection') && $this->input->getOption('connection')
            //    && $database['name'] != $this->input->getOption('connection')) {
            //    //we skip this schema because the connection name doesn't match the input value
            //    unset($this->tempSchemas[$tempSchema]);
            //    $filesystem->remove($file);
            //    continue;
            //}

            foreach ($database->table as $table) {
                if (isset($table['package'])) {
                    $table['package'] = $table['package'];
                } elseif (isset($table['namespace'])) {
                    $table['package'] = $packagePrefix . str_replace('\\', '.', $table['namespace']);
                } else {
                    $table['package'] = $database['package'];
                }
            }

            file_put_contents($file, $database->asXML());
        }
    }

    /**
     * Return a list of final schema files that will be processed.
     *
     * @param \Symfony\Component\HttpKernel\KernelInterface $kernel
     *
     * @return array
     */
    protected function getFinalSchemas(KernelInterface $kernel, BundleInterface $bundle = null)
    {
        if (null !== $bundle) {
            return $this->getSchemasFromBundle($bundle);
        }

        $finalSchemas = array();
        foreach ($kernel->getBundles() as $bundle) {
            $finalSchemas = array_merge($finalSchemas, $this->getSchemasFromBundle($bundle));
        }

        return $finalSchemas;
    }

    /**
     * @param \Symfony\Component\HttpKernel\Bundle\BundleInterface
     */
    protected function getSchemasFromBundle(BundleInterface $bundle)
    {
        $finalSchemas = array();

        if (is_dir($dir = $bundle->getPath().'/Resources/config')) {
            $finder  = new Finder();
            $schemas = $finder->files()->name('*schema.xml')->followLinks()->in($dir);

            if (iterator_count($schemas)) {
                foreach ($schemas as $schema) {
                    $logicalName = $this->transformToLogicalName($schema, $bundle);
                    $finalSchema = new \SplFileInfo($this->getFileLocator()->locate($logicalName));

                    $finalSchemas[(string) $finalSchema] = array($bundle, $finalSchema);
                }
            }
        }

        return $finalSchemas;
    }

    /*
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

    /**
     * Translates a list of connection names to their DSN equivalents.
     *
     * @param array $connections The names.
     *
     * @return array
     */
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
     * @return \Symfony\Component\Config\FileLocatorInterface
     */
    protected function getFileLocator()
    {
        return $this->getContainer()->get('file_locator');
    }

    /**
     * @param  \SplFileInfo    $schema
     * @param  BundleInterface $bundle
     * @return string
     */
    protected function transformToLogicalName(\SplFileInfo $schema, BundleInterface $bundle)
    {
        $schemaPath = str_replace(
            $bundle->getPath(). DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR,
            '',
            $schema->getRealPath()
        );

        return sprintf('@%s/Resources/config/%s', $bundle->getName(), $schemaPath);
    }

    /**
     * Check the PropelConfiguration object.
     */
    protected function checkConfiguration()
    {
        $parameters = $this->getContainer()->getParameter('propel.configuration');

        if (0 === count($parameters)) {
            throw new \RuntimeException('Propel should be configured (no database configuration found).');
        }
    }

    /**
     * Return the package prefix for a given bundle.
     *
     * @param Bundle $bundle
     * @param string $baseDirectory The base directory to exclude from prefix.
     *
     * @return string
     */
    protected function getPackagePrefix(Bundle $bundle, $baseDirectory = '')
    {
        $parts  = explode(DIRECTORY_SEPARATOR, realpath($bundle->getPath()));
        $length = count(explode('\\', $bundle->getNamespace())) * (-1);

        $prefix = implode(DIRECTORY_SEPARATOR, array_slice($parts, 0, $length));
        $prefix = ltrim(str_replace($baseDirectory, '', $prefix), DIRECTORY_SEPARATOR);

        if (!empty($prefix)) {
            $prefix = str_replace(DIRECTORY_SEPARATOR, '.', $prefix).'.';
        }

        return $prefix;
    }

    /**
     * Return the current Propel cache directory.
     *
     * @return string The current Propel cache directory.
     */
    protected function getCacheDir()
    {
        return $this->cacheDir;
    }
}
