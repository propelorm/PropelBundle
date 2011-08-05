<?php

namespace Propel\PropelBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Util\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Wrapper command for Phing tasks
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author William DURAND <william.durand1@gmail.com>
 */
abstract class PhingCommand extends ContainerAwareCommand
{
    protected $additionalPhingArgs = array();
    protected $tempSchemas = array();
    protected $tmpDir = null;
    protected $buffer = null;
    protected $buildPropertiesFile = null;

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->checkConfiguration();
    }

    /**
     * Call a Phing task.
     *
     * @param string $taskName  A Propel task name.
     * @param array $properties An array of properties to pass to Phing.
     */
    protected function callPhing($taskName, $properties = array())
    {
        $kernel = $this->getApplication()->getKernel();

        $filesystem = new Filesystem();

        if (isset($properties['propel.schema.dir'])) {
            $this->tmpDir = $properties['propel.schema.dir'];
        } else {
            $this->tmpDir = sys_get_temp_dir().'/propel-gen';
            $filesystem->remove($this->tmpDir);
            $filesystem->mkdir($this->tmpDir);
        }

        foreach ($kernel->getBundles() as $bundle) {
            if (is_dir($dir = $bundle->getPath().'/Resources/config')) {
                $finder = new Finder();
                $schemas = $finder->files()->name('*schema.xml')->followLinks()->in($dir);

                $parts = explode(DIRECTORY_SEPARATOR, realpath($bundle->getPath()));
                $length = count(explode('\\', $bundle->getNamespace())) * (-1);
                $prefix = implode('.', array_slice($parts, 1, $length));

                foreach ($schemas as $schema) {
                    $tempSchema = md5($schema).'_'.$schema->getBaseName();
                    $this->tempSchemas[$tempSchema] = array(
                        'bundle' => $bundle->getName(),
                        'basename' => $schema->getBaseName(),
                        'path' =>$schema->getPathname()
                    );
                    $file = $this->tmpDir.DIRECTORY_SEPARATOR.$tempSchema;
                    $filesystem->copy((string) $schema, $file);

                    // the package needs to be set absolute
                    // besides, the automated namespace to package conversion has not taken place yet
                    // so it needs to be done manually
                    $database = simplexml_load_file($file);
                    if (isset($database['package'])) {
                        $database['package'] = $prefix . '.' . $database['package'];
                    } elseif (isset($database['namespace'])) {
                        $database['package'] = $prefix . '.' . str_replace('\\', '.', $database['namespace']);
                    } else {
                        throw new \RuntimeException(sprintf('Please define a `package` attribute or a `namespace` attribute for schema `%s`', $schema->getBaseName()));
                    }

                    foreach ($database->table as $table)
                    {
                        if (isset($table['package'])) {
                            $table['package'] = $prefix . '.' . $table['package'];
                        } elseif (isset($table['namespace'])) {
                            $table['package'] = $prefix . '.' . str_replace('\\', '.', $table['namespace']);
                        } else {
                            $table['package'] = $database['package'];
                        }
                    }

                    file_put_contents($file, $database->asXML());
                }
            }
        }

        // build.properties
        $this->buildPropertiesFile = $kernel->getRootDir().'/config/propel.ini';
        $filesystem->touch($this->buildPropertiesFile);
        $filesystem->copy($this->buildPropertiesFile, $this->tmpDir.'/build.properties');
        // Required by the Phing task
        $this->createBuildTimeFile($this->tmpDir.'/buildtime-conf.xml');

        $args = array();

        $properties = array_merge(array(
            'propel.database'   => 'mysql',
            'project.dir'       => $this->tmpDir,
            'propel.output.dir' => $kernel->getRootDir().'/propel',
            'propel.php.dir'    => '/',
            'propel.packageObjectModel' => true,
        ), $properties);
        $properties = array_merge(
            $properties,
            $this->getContainer()->get('propel.build_properties')->getProperties()
        );
        foreach ($properties as $key => $value) {
            $args[] = "-D$key=$value";
        }

        // Build file
        $args[] = '-f';
        $args[] = realpath($this->getContainer()->getParameter('propel.path').'/generator/build.xml');

        $bufferPhingOutput = !$this->getContainer()->getParameter('kernel.debug');

        // Add any arbitrary arguments last
        foreach ($this->additionalPhingArgs as $arg) {
            if (in_array($arg, array('verbose', 'debug'))) {
                $bufferPhingOutput = false;
            }

            $args[] = '-'.$arg;
        }

        $args[] = $taskName;

        // enable output buffering
        Phing::setOutputStream(new \OutputStream(fopen('php://output', 'w')));
        Phing::setErrorStream(new \OutputStream(fopen('php://output', 'w')));
        Phing::startup();
        Phing::setProperty('phing.home', getenv('PHING_HOME'));

        ob_start();

        $phing = new Phing();
        $returnStatus = true; // optimistic way

        try {
            $phing->execute($args);
            $phing->runBuild();

            $this->buffer = ob_get_contents();
            $returnStatus = false !== preg_match('#failed. Aborting.#', $this->buffer);
        } catch(Exception $e) {
            $returnStatus = false;
        }

        if ($bufferPhingOutput) {
            ob_end_clean();
        } else {
            ob_end_flush();
        }

        chdir($kernel->getRootDir());

        return $returnStatus;
    }

    /**
     * Write an XML file which represents propel.configuration
     *
     * @param string $file  Should be 'buildtime-conf.xml'.
     */
    protected function createBuildTimeFile($file)
    {
        $container = $this->getContainer();

        if (!$container->has('propel.configuration')) {
            throw new \InvalidArgumentException('Could not find Propel configuration.');
        }

        $xml = strtr(<<<EOT
<?xml version="1.0"?>
<config>
  <propel>
    <datasources default="%default_connection%">

EOT
        , array('%default_connection%' => $container->getParameter('propel.dbal.default_connection')));

        $propelConfiguration = $container->get('propel.configuration');
        foreach ($propelConfiguration['datasources'] as $name => $datasource) {
            $xml .= strtr(<<<EOT
      <datasource id="%name%">
        <adapter>%adapter%</adapter>
        <connection>
          <dsn>%dsn%</dsn>
          <user>%username%</user>
          <password>%password%</password>
          <charset>%charset%</charset>
        </connection>
      </datasource>

EOT
            , array(
                '%name%'     => $name,
                '%adapter%'  => $datasource['adapter'],
                '%dsn%'      => $datasource['connection']['dsn'],
                '%username%' => $datasource['connection']['user'],
                '%password%' => $datasource['connection']['password'],
                '%charset%'  => $datasource['connection']['settings']['charset']['value'],
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
     * Returns an array of properties as key/value pairs from an input file.
     *
     * @param string $file  A file properties.
     * @return array        An array of properties as key/value pairs.
     */
    protected function getProperties($file)
    {
        $properties = array();

        if (false === $lines = @file($file)) {
            throw new sfCommandException('Unable to parse contents of the "sqldb.map" file.');
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ('' == $line) {
                continue;
            }

            if (in_array($line[0], array('#', ';'))) {
                continue;
            }

            $pos = strpos($line, '=');
            $properties[trim(substr($line, 0, $pos))] = trim(substr($line, $pos + 1));
        }

        return $properties;
    }

    /**
     * Returns the current tmpfile.
     *
     * @return string   The current tmpfile
     */
    protected function getTmpDir() {
        return $this->tmpDir;
    }

    /**
     * Get connection by checking the input option named 'connection'.
     * Returns the default connection if no option specified or an exception
     * if the specified connection doesn't exist.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throw \InvalidArgumentException If the connection does not exist.
     * @return array
     */
    protected function getConnection(InputInterface $input, OutputInterface $output) {
        $container = $this->getContainer();
        $propelConfiguration = $container->get('propel.configuration');
        $name = $input->getOption('connection') ?: $container->getParameter('propel.dbal.default_connection');


        if (isset($propelConfiguration['datasources'][$name])) {
            $defaultConfig = $propelConfiguration['datasources'][$name];
        } else {
            throw new \InvalidArgumentException(sprintf('Connection named %s doesn\'t exist', $name));
        }

        $output->writeln(sprintf('<info>Use connection named <comment>%s</comment></info>', $name));

        return array($name, $defaultConfig);
    }

    /**
     * Extract the database name from a given DSN
     * @param string $dsn   A DSN
     * @return string       The database name extracted from the given DSN
     */
    protected function parseDbName($dsn) {
        preg_match('#dbname=([a-zA-Z0-9\_]+)#', $dsn, $matches);
        return $matches[1];
    }

    /**
     * Write Propel output as summary.
     *
     * @param $taskname A task name
     */
    protected function summary($output, $taskname) {
        foreach (explode("\n", $this->buffer) as $line) {
            if (false !== strpos($line, '[' . $taskname . ']')) {
                $arr  = preg_split('#\[' . $taskname . '\] #', $line);
                $info = $arr[1];

                if ('"' === $info[0]) {
                    $info = sprintf('<info>%s</info>', $info);
                }

                $output->writeln($info);
            }
        }
    }

    /**
     * Check the PropelConfiguration object.
     */
    protected function checkConfiguration()
    {
        $parameters = $this->getContainer()->get('propel.configuration')->getParameters();

        if (!isset($parameters['datasources']) || 0 === count($parameters['datasources'])) {
            throw new \RuntimeException('Propel should be configured (no database configuration found).');
        }
    }

    /**
     * Comes from the SensioGeneratorBundle.
     * @see https://github.com/sensio/SensioGeneratorBundle/blob/master/Command/Helper/DialogHelper.php#L52
     */
    public function writeSection(OutputInterface $output, $text, $style = 'bg=blue;fg=white')
    {
        $output->writeln(array(
            '',
            $this->getHelperSet()->get('formatter')->formatBlock($text, $style, true),
            '',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function askConfirmation(OutputInterface $output, $question, $default = null)
    {
        return $this->getHelperSet()->get('dialog')->askConfirmation($output, $question, $default);
    }
}
