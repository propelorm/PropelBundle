<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\DataFixtures;

use \Propel;

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
     * @var \DatabaseMap
     */
    protected $dbMap;

    /**
     * Default constructor
     *
     * @param string $rootDir   The root directory.
     */
    public function __construct($rootDir)
    {
        $this->rootDir = $rootDir;
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
     * @param string $connectionName    A connection name.
     */
    protected function loadMapBuilders($connectionName = null)
    {
        if (null !== $this->dbMap) {
            return;
        }

        $this->dbMap = Propel::getDatabaseMap($connectionName);

        if (0 === count($this->dbMap->getTables())) {
            $finder = new Finder();
            $files = $finder->files()->name('*TableMap.php')->in($this->getRootDir() . '/../')->exclude('Tests');

            foreach ($files as $file) {
                $class = $this->guessFullClassName($file->getRelativePath(), basename($file, '.php'));

                if (null !== $class) {
                    $this->dbMap->addTableFromMapClass($class);
                }
            }
        }
    }

    /**
     * Try to find a valid class with its namespace based on the filename.
     * Based on the PSR-0 standard, the namespace should be the directory structure.
     *
     * @param string $path  The relative path of the file.
     * @param string $shortClassName    The short class name aka the filename without extension.
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
}
