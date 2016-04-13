<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Command;

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

    /**
     * @var OutputInterface
     */
    protected $output;

    protected $tempSchemas = [];

    use FormattingHelpers;

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $kernel = $this->getApplication()->getKernel();

        $this->input = $input;
        $this->output = $output;
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

        $finalSchemas = $this->getFinalSchemas($kernel, $this->bundle);
        foreach ($finalSchemas as $schema) {
            /** @var Bundle $bundle */
            list($bundle, $finalSchema) = $schema;

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

                $database['package'] = $this->getPackageFromBundle($bundle, (string)$database['namespace']);
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
                    $this->output->writeln(sprintf(
                        '<info>Skipped schema %s due to database name missmatch (%s not in [%s]).</info>',
                        $finalSchema->getPathname(),
                        $database['name'],
                        implode(',', $connections)
                    ));
                    continue;
                }
            }

            foreach ($database->table as $table) {
                if (isset($table['package'])) {
                    $table['package'] = $table['package'];
                } elseif (isset($table['namespace'])) {
                    $table['package'] = $this->getPackageFromBundle($bundle, (string)$table['namespace']);
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
        $propelConfig = $this->getContainer()->getParameter('propel.configuration');

        //needed because because Propel2's configuration tree is a bit different
        //propel.runtime.logging is PropelBundle feature only.
        unset($propelConfig['runtime']['logging']);

        $config = array(
            'propel' => $propelConfig
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
        // Add user and password to dsn string
        $dsn = explode(';', $connection['dsn']);
        if (isset($connection['user'])) {
            $dsn[] = 'user=' . urlencode($connection['user']);
        }
        if (isset($connection['password'])) {
            $dsn[] = 'password=' . urlencode($connection['password']);
        }

        return implode(';', $dsn);
    }

    /**
     * @return \Symfony\Component\Config\FileLocatorInterface
     */
    protected function getSchemaLocator()
    {
        return $this->getContainer()->get('propel.schema_locator');
    }

    /**
     * @param Bundle $bundle
     * @param string $namespace
     *
     * @return string
     */
    protected function getPackageFromBundle(Bundle $bundle, $namespace)
    {
        //find relative path from namespace to bundle->getNamespace()
        $baseNamespace = (new \ReflectionClass($bundle))->getNamespaceName();
        if (0 === strpos($namespace, $baseNamespace)) {
            //base namespace fits
            //eg.
            //  Base: Jarves/JarvesBundle => Jarves
            //  Model namespace: Jarves\Model
            //  strpos(Jarves\Model, Jarves) === 0
            // $namespaceDiff = Model

            $namespaceDiff = substr($namespace, strlen($baseNamespace) + 1);

            $bundlePath = realpath($bundle->getPath()) . '/' . str_replace('\\', '/', $namespaceDiff);
            $appPath = realpath($this->getApplication()->getKernel()->getRootDir() . '/..');

            $path = static::getRelativePath($bundlePath, $appPath);

            return str_replace('/', '.', $path);
        }

        //does not match or its a absolute path, so return it without suffix
        if ('\\' === $namespace[0]) {
            $namespace = substr($namespace, 1);
        }

        return str_replace('\\', '.', $namespace);
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
     * Returns a relative path from $path to $current.
     *
     * @param string $from
     * @param string $to relative to this
     *
     * @return string relative path without trailing slash
     */
    public static function getRelativePath($from, $to)
    {
        $from = '/' . trim($from, '/');
        $to = '/' . trim($to, '/');

        if (0 === $pos = strpos($from, $to)) {
            return substr($from, strlen($to) + ('/' === $to ? 0 : 1));
        }

        $result = '';
        while ($to && false === strpos($from, $to)) {
            $result .= '../';
            $to = substr($to, 0, strrpos($to, '/'));
        }

        return !$to /*we reached root*/ ? $result . substr($from, 1) : $result. substr($from, strlen($to) + 1);
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

        return !empty($config['generator']['defaultConnection']) ? $config['generator']['defaultConnection'] : key($config['database']['connections']);
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
