<?php

namespace Propel\PropelBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\HttpKernel\Util\Filesystem;
use Symfony\Component\Finder\Finder;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Wrapper command for Phing tasks
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author William DURAND <william.durand1@gmail.com>
 */
abstract class PhingCommand extends Command
{
    protected $additionalPhingArgs = array();
    protected $tempSchemas = array();
    protected $tmpDir = null;
    protected $buffer = null;

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
                $prefix = implode('.', array_slice($parts, 1, -2));

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

        $filesystem->touch($this->tmpDir.'/build.properties');
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
        foreach ($properties as $key => $value) {
            $args[] = "-D$key=$value";
        }

        // Build file
        $args[] = '-f';
        $args[] = realpath($kernel->getContainer()->getParameter('propel.path').'/generator/build.xml');

        $bufferPhingOutput = $kernel->getContainer()->getParameter('kernel.debug');

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
        Phing::startup();
        Phing::setProperty('phing.home', getenv('PHING_HOME'));

        if ($bufferPhingOutput) {
            ob_start();
        }

        $m = new Phing();
        $m->execute($args);
        $m->runBuild();

        if ($bufferPhingOutput) {
            $this->buffer = ob_get_contents();
            ob_end_clean();
        }

        chdir($kernel->getRootDir());

        $ret = true;
        return $ret;
    }

    /**
     * Write an XML file which represents propel.configuration
     *
     * @param string $file  Should be 'buildtime-conf.xml'.
     */
    protected function createBuildTimeFile($file)
    {
        $container = $this->getApplication()->getKernel()->getContainer();

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
        </connection>
      </datasource>

EOT
            , array(
                '%name%'     => $name,
                '%adapter%'  => $datasource['adapter'],
                '%dsn%'      => $datasource['connection']['dsn'],
                '%username%' => $datasource['connection']['user'],
                '%password%' => $datasource['connection']['password'],
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
}
