<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\DataFixtures;

use Propel\Runtime\Map\DatabaseMap;
use Propel\Runtime\Propel;
use Symfony\Component\Finder\Finder;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
abstract class AbstractDataHandler
{
    /**
     * @var string
     */
    protected $rootDir;

    /**
     * @var \PDO
     */
    protected $con;

    /**
     * @var DatabaseMap
     */
    protected $dbMap;

    /**
     * @var array
     */
    protected $datasources = array();

    /**
     * Default constructor
     *
     * @param string $rootDir     The root directory.
     * @param array  $datasources
     */
    public function __construct($rootDir, array $datasources)
    {
        $this->rootDir = $rootDir;
        $this->datasources = $datasources;
    }

    /**
     * @return string
     */
    protected function getRootDir()
    {
        return $this->rootDir;
    }

    /**
     * Load Map builders.
     *
     * @param string $connectionName A connection name.
     */
    protected function loadMapBuilders($connectionName = null)
    {
        if (null !== $this->dbMap) {
            return;
        }

        $this->dbMap = Propel::getDatabaseMap($connectionName);
        if (0 === count($this->dbMap->getTables())) {
            $finder = new Finder();
            $files  = $finder
                ->files()->name('*TableMap.php')
                ->in($this->getModelSearchPaths($connectionName))
                ->notName('TableMap.php')
                ->exclude('PropelBundle')
                ->exclude('Tests');

            foreach ($files as $file) {
                $class = $this->guessFullClassName($file->getRelativePath(), basename($file, '.php'));

                if (null !== $class && $this->isInDatabase($class, $connectionName)) {
                    $this->dbMap->addTableFromMapClass($class);
                }
            }
        }
    }

    /**
     * Check if a table is in a database
     *
     * @param string $class
     * @param string $connectionName
     *
     * @return boolean
     */
    protected function isInDatabase($class, $connectionName)
    {
        return constant($class.'::DATABASE_NAME') === $connectionName;
    }

    /**
     * Try to find a valid class with its namespace based on the filename.
     * Based on the PSR-0 standard, the namespace should be the directory structure.
     *
     * @param string $path           The relative path of the file.
     * @param string $shortClassName The short class name aka the filename without extension.
     *
     * @return string|null
     */
    private function guessFullClassName($path, $shortClassName)
    {
        $array = array();
        $path  = str_replace('/', '\\', $path);

        $array[] = $path;
        while ($pos = strpos($path, '\\')) {
            $path = substr($path, $pos + 1, strlen($path));
            $array[] = $path;
        }

        $array = array_reverse($array);
        while ($ns = array_pop($array)) {

            $class = $ns . '\\' . $shortClassName;
            if (class_exists($class)) {
                return $class;
            }
        }

        return null;
    }

    /**
     * Gets the search path for models out of the configuration.
     *
     * @param string $connectionName A connection name.
     *
     * @return string[]
     */
    protected function getModelSearchPaths($connectionName)
    {
        $searchPath = array();

        if (!empty($this->datasources['database']['connections'][$connectionName]['model_paths'])) {
            $modelPaths = $this->datasources['database']['connections'][$connectionName]['model_paths'];
            foreach ($modelPaths as $modelPath) {
                $searchPath[] = $this->getRootDir() . '/' . $modelPath;
            }
        } else {
            $searchPath[] = $this->getRootDir() . '/';
        }

        return $searchPath;
    }
}
