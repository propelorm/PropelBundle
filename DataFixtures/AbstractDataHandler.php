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
     */
    protected $con;
    /**
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
     * Loads all map builders.
     */
    protected function loadMapBuilders()
    {
        $dbMap  = Propel::getDatabaseMap();

        $finder = new Finder();
        $files  = $finder->files()->name('*TableMap.php')->in($this->getRootDir() . '/../');

        foreach ($files as $file) {
            $omClass = basename($file, 'TableMap.php');
            if (class_exists($omClass) && is_subclass_of($omClass, 'BaseObject')) {
                $tableMapClass = basename($file, '.php');
                $dbMap->addTableFromMapClass($tableMapClass);
            }
        }
    }
}
