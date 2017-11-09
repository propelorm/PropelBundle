<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\DataFixtures\Loader;

/**
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class DataWiper extends AbstractDataLoader
{
    /**
     * Clears the database completely.
     *
     * @param array  $files          A set of files containing datas to load.
     * @param string $connectionName The Propel connection name
     */
    public function load($files = array(), $connectionName)
    {
        $this->deletedClasses = array();
        $this->loadMapBuilders($connectionName);

        $this->con = \Propel::getConnection($connectionName);

        try {
            $this->con->beginTransaction();
            if ('mysql' === $this->con->getAttribute(\PDO::ATTR_DRIVER_NAME)) {
                $this->con->exec('SET FOREIGN_KEY_CHECKS = 0;');
            }

            $tables = array();
            foreach ($this->dbMap->getTables() as $eachTable) {
                /* @var $eachTable \TableMap */
                $tables[$eachTable->getClassname()] = array();
            }

            $this->deleteCurrentData($tables);

            if ('mysql' === $this->con->getAttribute(\PDO::ATTR_DRIVER_NAME)) {
                $this->con->exec('SET FOREIGN_KEY_CHECKS = 1;');
            }
            $this->con->commit();
        } catch (\Exception $e) {
            $this->con->rollBack();

            throw $e;
        }
    }

    /**
     * Not used by this data loader.
     *
     * @param string $file A filename.
     *
     * @return array
     */
    protected function transformDataToArray($file)
    {
        return array();
    }
}
