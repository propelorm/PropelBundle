<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
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
     * @var \Symfony\Component\HttpKernel\Bundle\BundleInterface
     */
    protected $bundle = null;

    /**
     * @var InputInterface
     */
    protected $input;

    use FormattingHelpers;

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $kernel = $this->getApplication()->getKernel();

        $this->input = $input;
        $this->cacheDir = $kernel->getCacheDir().'/propel';

        if ($input->hasArgument('bundle') && '@' === substr($input->getArgument('bundle'), 0, 1)) {
            $this->bundle = $this
                ->getContainer()
                ->get('kernel')
                ->getBundle(substr($input->getArgument('bundle'), 1));
        }
    }

    /**
     * Create all the files needed by Propel's commands.
     */
    protected function setupBuildTimeFiles()
    {
        $kernel = $this->getApplication()->getKernel();

        $fs = new Filesystem();
        $fs->mkdir($this->cacheDir);

        // collect all schemas
        $this->copySchemas($kernel, $this->cacheDir);

        // propel.json
        $this->createPropelConfigurationFile($this->cacheDir.'/propel.json');
    }

    /**
     * @param KernelInterface $kernel   The application kernel.
     * @param string          $cacheDir The directory in which the schemas will
     *                                  be copied.
     */
    protected function copySchemas(KernelInterface $kernel, $cacheDir)
    {
        $filesystem = new Filesystem();
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

            if ($this->input->hasOption('connection')) {
                $connections = $this->input->getOption('connection') ?: array($this->getDefaultConnection());

                if (!in_array((string) $database['name'], $connections)) {
                    // we skip this schema because the connection name doesn't
                    // match the input values
                    unset($this->tempSchemas[$tempSchema]);
                    $filesystem->remove($file);
                    continue;
                }
            }

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
     * @param KernelInterface $kernel The application kernel.
     * @param BundleInterface $bundle If given, only the bundle's schemas will
     *                                be returned.
     *
     * @return array A list of schemas.
     */
    protected function getFinalSchemas(KernelInterface $kernel, BundleInterface $bundle = null)
    {
        if (null !== $bundle) {
            return $this->getSchemaLocator()->locateFromBundle($bundle);
        }

        return $this->getSchemaLocator()->locateFromBundles($kernel->getBundles());
    }

    /**
     * Run a Symfony command.
     *
     * @param Command         $command    The command to run.
     * @param array           $parameters An array of parameters to give to the command.
     * @param InputInterface  $input      An InputInterface instance
     * @param OutputInterface $output     An OutputInterface instance
     *
     * @return int The command return code.
     */
    protected function runCommand(Command $command, array $parameters, InputInterface $input, OutputInterface $output)
    {
        // add the command's name to the parameters
        array_unshift($parameters, $this->getName());

        // merge the default parameters
        $extraParameters = [
            '--verbose' => $input->getOption('verbose')
        ];

        if ($command->getDefinition()->hasOption('schema-dir')) {
            $extraParameters['--schema-dir'] = $this->cacheDir;
        }

        if ($command->getDefinition()->hasOption('config-dir')) {
            $extraParameters['--config-dir'] = $this->cacheDir;
        }

        $parameters = array_merge($extraParameters, $parameters);

        if ($input->hasOption('platform')) {
            if ($platform = $input->getOption('platform') ?: $this->getPlatform()) {
                $parameters['--platform'] = $platform;
            }
        }

        $command->setApplication($this->getApplication());

        // and run the sub-command
        return $command->run(new ArrayInput($parameters), $output);
    }

    /**
     * Create an XML file which represents propel.configuration
     *
     * @param string $file Should be 'propel.json'.
     */
    protected function createPropelConfigurationFile($file)
    {
        $config = array(
            'propel' => $this->getContainer()->getParameter('propel.configuration')
        );

        file_put_contents($file, json_encode($config, JSON_PRETTY_PRINT));
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
        $dsnList = array();
        foreach ($connections as $connection) {
            $dsnList[] = sprintf('%s=%s', $connection, $this->getDsn($connection));
        }

        return $dsnList;
    }

    /**
     * Get the data (host, user, ...) for a given connection.
     *
     * @param string $name The connection name.
     *
     * @return array The connection data.
     */
    protected function getConnectionData($name)
    {
        $knownConnections = $this->getContainer()->getParameter('propel.configuration');
        if (!isset($knownConnections['database']['connections'][$name])) {
            throw new \InvalidArgumentException(sprintf('Unknown connection "%s"', $name));
        }

        return $knownConnections['database']['connections'][$name];
    }

    /**
     * Get the DSN for a given connection.
     *
     * @param string $connectionName The connection name.
     *
     * @return string The DSN.
     */
    protected function getDsn($connectionName)
    {
        $connection = $this->getConnectionData($connectionName);

        return $connection['dsn'];
    }

    /**
     * @return \Symfony\Component\Config\FileLocatorInterface
     */
    protected function getSchemaLocator()
    {
        return $this->getContainer()->get('propel.schema_locator');
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

    /**
     * Extract the database name from a given DSN
     *
     * @param  string $dsn A DSN
     * @return string The database name extracted from the given DSN
     */
    protected function parseDbName($dsn)
    {
        preg_match('#dbname=([a-zA-Z0-9\_]+)#', $dsn, $matches);

        if (isset($matches[1])) {
            return $matches[1];
        }

        // e.g. SQLite
        return null;
    }

    /**
     * Returns the name of the migrations table.
     *
     * @return string
     */
    protected function getMigrationsTable()
    {
        $config = $this->getContainer()->getParameter('propel.configuration');

        return $config['migrations']['tableName'];
    }

    /**
     * Returns the name of the default connection.
     *
     * @return string
     */
    protected function getDefaultConnection()
    {
        $config = $this->getContainer()->getParameter('propel.configuration');

        return $config['generator']['defaultConnection'];
    }

    /**
     * Reads the platform class from the configuration
     *
     * @return string The platform class name.
     */
    protected function getPlatform()
    {
        $config = $this->getContainer()->getParameter('propel.configuration');
        return $config['generator']['platformClass'];
    }
}
