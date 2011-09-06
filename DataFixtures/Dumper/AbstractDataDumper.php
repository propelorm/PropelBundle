<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\DataFixtures\Dumper;

use \Propel;
use \BasePeer;
use \BaseObject;
use \ColumnMap;
use \PropelException;

use Propel\PropelBundle\DataFixtures\AbstractDataHandler;

/**
 * Abstract class to manage a common logic to dump data.
 *
 * @author William Durand <william.durand1@gmail.com>
 */
abstract class AbstractDataDumper extends AbstractDataHandler implements DataDumperInterface
{
    /**
     * {@inheritdoc}
     */
    public function dump($filename = null, $connectionName)
    {
        $this->loadMapBuilders();
        $this->con   = Propel::getConnection($connectionName);
        $this->dbMap = Propel::getDatabaseMap($connectionName);

        $array = $this->getDataAsArray($connectionName);

        if (null === $filename) {
            $filename = 'fixture_' . time();
        }

        $filename .= '.' . $this->getFileExtension();
        $data = $this->transformArrayToData($array);

        if (false === file_put_contents($filename, $data)) {
            throw new \Exception(sprintf('Cannot write file: %s', $filename));
        }
    }

    /**
     * Transforms an array of data to a specific format
     * depending on the specialized dumper. It should return
     * a string content ready to write in a file.
     *
     * @return string
     */
    abstract protected function transformArrayToData($data);

    /**
     * Returns the file extension depending on the specialized dumper.
     *
     * @return string
     */
    abstract protected function getFileExtension();

    /**
     * Dumps data to fixture from a given connection and
     * returns an array.
     *
     * @param string $connectionName    The connection name
     * @return array
     */
    protected getDataAsArray($connectionName)
    {
        $tables = array();
        foreach ($this->dbMap->getTables() as $table) {
            $tables[] = $table->getPhpName();
        }

        $tables = $this->fixOrderingOfForeignKeyData($tables);

        $dumpData = array();
        foreach ($tables as $tableName) {
            $tableMap    = $this->dbMap->getTable(constant(constant($tableName.'::PEER').'::TABLE_NAME'));
            $hasParent   = false;
            $haveParents = false;
            $fixColumn   = null;

            foreach ($tableMap->getColumns() as $column) {
                $col = strtolower($column->getName());
                if ($column->isForeignKey()) {
                    $relatedTable = $this->dbMap->getTable($column->getRelatedTableName());
                    if ($tableName === $relatedTable->getPhpName()) {
                        if ($hasParent) {
                            $haveParents = true;
                        } else {
                            $fixColumn = $column;
                            $hasParent = true;
                        }
                    }
                }
            }

            if ($haveParents) {
                // unable to dump tables having multi-recursive references
                continue;
            }

            // get db info
            $resultsSets = array();
            if ($hasParent) {
                $resultsSets[] = $this->fixOrderingOfForeignKeyDataInSameTable($resultsSets, $tableName, $fixColumn);
            } else {
                $in = array();
                foreach ($tableMap->getColumns() as $column) {
                    $in[] = strtolower($column->getName());
                }
                $stmt = $this
                    ->con
                    ->query(sprintf('SELECT %s FROM %s', implode(',', $in), constant(constant($tableName.'::PEER').'::TABLE_NAME')));

                $resultsSets[] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $stmt->closeCursor();
                unset($stmt);
            }

            foreach ($resultsSets as $rows) {
                if(count($rows) > 0 && !isset($dumpData[$tableName])) {
                    $dumpData[$tableName] = array();

                    foreach ($rows as $row) {
                        $pk          = $tableName;
                        $values      = array();
                        $primaryKeys = array();
                        $foreignKeys = array();

                        foreach ($tableMap->getColumns() as $column) {
                            $col = strtolower($column->getName());
                            $isPrimaryKey = $column->isPrimaryKey();

                            if (null === $row[$col]) {
                                continue;
                            }

                            if ($isPrimaryKey) {
                                $value = $row[$col];
                                $pk .= '_'.$value;
                                $primaryKeys[$col] = $value;
                            }

                            if ($column->isForeignKey()) {
                                $relatedTable = $this->dbMap->getTable($column->getRelatedTableName());
                                if ($isPrimaryKey) {
                                    $foreignKeys[$col] = $row[$col];
                                    $primaryKeys[$col] = $relatedTable->getPhpName().'_'.$row[$col];
                                } else {
                                    $values[$col] = $relatedTable->getPhpName().'_'.$row[$col];
                                    $values[$col] = strlen($row[$col]) ? $relatedTable->getPhpName().'_'.$row[$col] : '';
                                }
                            }
                            elseif (!$isPrimaryKey || ($isPrimaryKey && !$tableMap->isUseIdGenerator())) {
                                // We did not want auto incremented primary keys
                                $values[$col] = $row[$col];
                            }
                        }

                        if (count($primaryKeys) > 1 || (count($primaryKeys) > 0 && count($foreignKeys) > 0)) {
                            $values = array_merge($primaryKeys, $values);
                        }

                        $dumpData[$tableName][$pk] = $values;
                    }
                }
            }
        }

        return $dumpData;
    }

    /**
     * Fixes the ordering of foreign key data, by outputting data
     * a foreign key depends on before the table with the foreign key.
     *
     * @param array $classes    The array with the class names
     * @return array
     */
    protected function fixOrderingOfForeignKeyData($classes)
    {
        // reordering classes to take foreign keys into account
        for ($i = 0, $count = count($classes); $i < $count; $i++) {
            $class    = $classes[$i];
            $tableMap = $this->dbMap->getTable(constant(constant($class.'::PEER').'::TABLE_NAME'));

            foreach ($tableMap->getColumns() as $column) {
                if ($column->isForeignKey()) {
                    $relatedTable = $this->dbMap->getTable($column->getRelatedTableName());
                    $relatedTablePos = array_search($relatedTable->getPhpName(), $classes);

                    // check if relatedTable is after the current table
                    if ($relatedTablePos > $i) {
                        // move related table 1 position before current table
                        $classes = array_merge(
                            array_slice($classes, 0, $i),
                            array($classes[$relatedTablePos]),
                            array_slice($classes, $i, $relatedTablePos - $i),
                            array_slice($classes, $relatedTablePos + 1)
                        );
                        // we have moved a table, so let's see if we are done
                        return $this->fixOrderingOfForeignKeyData($classes);
                    }
                }
            }
        }
        return $classes;
    }

    protected function fixOrderingOfForeignKeyDataInSameTable($resultsSets, $tableName, $column, $in = null)
    {
        $sql = sprintf('SELECT * FROM %s WHERE %s %s',
            constant(constant($tableName.'::PEER').'::TABLE_NAME'),
            strtolower($column->getName()),
            null === $in ? 'IS NULL' : 'IN ('.$in.')');

        $stmt = $this->con->prepare($sql);
        $stmt->execute();

        $in = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $in[] = "'".$row[strtolower($column->getRelatedColumnName())]."'";
            $resultsSets[] = $row;
        }

        if ($in = implode(', ', $in)) {
            $resultsSets = $this->fixOrderingOfForeignKeyDataInSameTable($resultsSets, $tableName, $column, $in);
        }

        return $resultsSets;
    }
}
