<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\DataFixtures\Loader;

use \BasePeer;
use \BaseObject;
use \Propel;
use \PropelColumnTypes;
use \PropelException;
use Propel\PropelBundle\DataFixtures\AbstractDataHandler;
use Propel\PropelBundle\Util\PropelInflector;

/**
 * Abstract class to manage a common logic to load datas.
 *
 * @author William Durand <william.durand1@gmail.com>
 */
abstract class AbstractDataLoader extends AbstractDataHandler implements DataLoaderInterface
{
    /**
     * @var array
     */
    protected $deletedClasses = array();

    /**
     * @var array
     */
    protected $object_references = array();

    /**
     * Transforms a file containing data in an array.
     *
     * @param  string $file A filename.
     * @return array
     */
    abstract protected function transformDataToArray($file);

    /**
     * {@inheritdoc}
     */
    public function load($files = array(), $connectionName)
    {
        $nbFiles = 0;
        $this->deletedClasses = array();

        $this->loadMapBuilders($connectionName);
        $this->con = Propel::getConnection($connectionName);

        try {
            $this->con->beginTransaction();

            $datas = array();
            foreach ($files as $file) {
                $content = $this->transformDataToArray($file);

                if (count($content) > 0) {
                    $datas = array_merge_recursive($datas, $content);
                    $nbFiles++;
                }
            }

            $this->deleteCurrentData($datas);
            $this->loadDataFromArray($datas);

            $this->con->commit();
        } catch (\Exception $e) {
            $this->con->rollBack();
            throw $e;
        }

        return $nbFiles;
    }

    /**
     * Deletes current data.
     *
     * @param array $data The data to delete
     */
    protected function deleteCurrentData($data = null)
    {
        if ($data !== null) {
            $classes = array_keys($data);
            foreach (array_reverse($classes) as $class) {
                $class = trim($class);
                if (in_array($class, $this->deletedClasses)) {
                    continue;
                }
                $this->deleteClassData($class);
            }
        }
    }

    /**
     * Delete data for a given class, and for its ancestors (if any).
     *
     * @param string $class Class name to delete
     */
    protected function deleteClassData($class)
    {
        // Check that peer class exists before calling doDeleteAll()
        $peerClass = constant($class.'::PEER');
        if (!class_exists($peerClass)) {
            throw new \InvalidArgumentException(sprintf('Unknown class "%sPeer".', $class));
        }

        // bypass the soft_delete behavior if enabled
        $deleteMethod = method_exists($peerClass, 'doForceDeleteAll') ? 'doForceDeleteAll' : 'doDeleteAll';
        call_user_func(array($peerClass, $deleteMethod), $this->con);

        $this->deletedClasses[] = $class;

        // Remove ancestors data
        if (false !== ($parentClass = get_parent_class(get_parent_class($class)))) {
            $reflectionClass = new \ReflectionClass($parentClass);
            if (!$reflectionClass->isAbstract()) {
                $this->deleteClassData($parentClass);
            }
        }
    }

    /**
     * Loads the data using the generated data model.
     *
     * @param array $data The data to be loaded
     */
    protected function loadDataFromArray($data = null)
    {
        if ($data === null) {
            return;
        }

        foreach ($data as $class => $datas) {
            $class = trim($class);
            if ('\\' == $class[0]) {
                $class = substr($class, 1);
            }
            $tableMap     = $this->dbMap->getTable(constant(constant($class.'::PEER').'::TABLE_NAME'));
            $column_names = call_user_func_array(array(constant($class.'::PEER'), 'getFieldNames'), array(BasePeer::TYPE_FIELDNAME));

            // iterate through datas for this class
            // might have been empty just for force a table to be emptied on import
            if (!is_array($datas)) {
                continue;
            }

            foreach ($datas as $key => $data) {
                // create a new entry in the database
                if (!class_exists($class)) {
                    throw new \InvalidArgumentException(sprintf('Unknown class "%s".', $class));
                }

                $obj = new $class();

                if (!$obj instanceof BaseObject) {
                    throw new \RuntimeException(
                        sprintf('The class "%s" is not a Propel class. There is probably another class named "%s" somewhere.', $class, $class)
                    );
                }

                if (!is_array($data)) {
                    throw new \InvalidArgumentException(sprintf('You must give a name for each fixture data entry (class %s).', $class));
                }

                foreach ($data as $name => $value) {
                    if (is_array($value) && 's' === substr($name, -1)) {
                        try {
                            // many to many relationship
                            $this->loadManyToMany($obj, substr($name, 0, -1), $value);

                            continue;
                        } catch (PropelException $e) {
                            // Check whether this is actually an array stored in the object.
                            if ('Cannot fetch TableMap for undefined table: ' . substr($name, 0, -1) === $e->getMessage()) {
                                if (PropelColumnTypes::PHP_ARRAY !== $tableMap->getColumn($name)->getType()
                                    && PropelColumnTypes::OBJECT !== $tableMap->getColumn($name)->getType()) {
                                    throw $e;
                                }
                            }
                        }
                    }

                    $isARealColumn = true;
                    if ($tableMap->hasColumn($name)) {
                        $column = $tableMap->getColumn($name);
                    } elseif ($tableMap->hasColumnByPhpName($name)) {
                        $column = $tableMap->getColumnByPhpName($name);
                    } else {
                        $isARealColumn = false;
                    }

                    // foreign key?
                    if ($isARealColumn) {
                        /*
                         * A column, which is a PrimaryKey (self referencing, e.g. versionable behavior),
                         * but which is not a ForeignKey (e.g. delegatable behavior on 1:1 relation).
                         */
                        if ($column->isPrimaryKey() && null !== $value && !$column->isForeignKey()) {
                            if (isset($this->object_references[$class.'_'.$value])) {
                                $obj = $this->object_references[$class.'_'.$value];

                                continue;
                            }
                        }

                        if ($column->isForeignKey() && null !== $value) {
                            $relatedTable = $this->dbMap->getTable($column->getRelatedTableName());
                            if (!isset($this->object_references[$relatedTable->getClassname().'_'.$value])) {
                                throw new \InvalidArgumentException(
                                    sprintf('The object "%s" from class "%s" is not defined in your data file.', $value, $relatedTable->getClassname())
                                );
                            }
                            $value = $this
                                ->object_references[$relatedTable->getClassname().'_'.$value]
                                ->getByName($column->getRelatedName(), BasePeer::TYPE_COLNAME);
                        }
                    }

                    if (false !== $pos = array_search($name, $column_names)) {
                        $obj->setByPosition($pos, $value);
                    } elseif (is_callable(array($obj, $method = 'set'.ucfirst(PropelInflector::camelize($name))))) {
                        $obj->$method($value);
                    } else {
                        throw new \InvalidArgumentException(sprintf('Column "%s" does not exist for class "%s".', $name, $class));
                    }
                }

                $obj->save($this->con);

                $this->saveParentReference($class, $key, $obj);
            }
        }
    }

    /**
     * Save a reference to the specified object (and its ancestors) before loading them.
     *
     * @param string     $class Class name of passed object
     * @param string     $key   Key identifying specified object
     * @param BaseObject $obj   A Propel object
     */
    protected function saveParentReference($class, $key, &$obj)
    {
        if (method_exists($obj, 'getPrimaryKey')) {

            $this->object_references[$class.'_'.$key] = $obj;

            // Get parent (schema ancestor) of parent (Propel base class) in case of inheritance
            if (false !== ($parentClass = get_parent_class(get_parent_class($class)))) {

                $reflectionClass = new \ReflectionClass($parentClass);
                if (!$reflectionClass->isAbstract()) {
                    $parentObj = new $parentClass;
                    $parentObj->fromArray($obj->toArray());
                    $this->saveParentReference($parentClass, $key, $parentObj);
                }

            }

        }
    }

    /**
     * Loads many to many objects.
     *
     * @param BaseObject $obj             A Propel object
     * @param string     $middleTableName The middle table name
     * @param array      $values          An array of values
     */
    protected function loadManyToMany($obj, $middleTableName, $values)
    {
        $middleTable = $this->dbMap->getTable($middleTableName);
        $middleClass = $middleTable->getClassname();
        $tableName   = constant(constant(get_class($obj).'::PEER').'::TABLE_NAME');

        foreach ($middleTable->getColumns() as $column) {
            if ($column->isForeignKey()) {
                if ($tableName !== $column->getRelatedTableName()) {
                    $relatedClass  = $this->dbMap->getTable($column->getRelatedTableName())->getClassname();
                    $relatedSetter = 'set' . $column->getRelation()->getName();
                } else {
                    $setter = 'set' . $column->getRelation()->getName();
                }
            }
        }

        if (!isset($relatedClass)) {
            throw new \InvalidArgumentException(sprintf('Unable to find the many-to-many relationship for object "%s".', get_class($obj)));
        }

        foreach ($values as $value) {
            if (!isset($this->object_references[$relatedClass.'_'.$value])) {
                throw new \InvalidArgumentException(
                    sprintf('The object "%s" from class "%s" is not defined in your data file.', $value, $relatedClass)
                );
            }

            $middle = new $middleClass();
            $middle->$setter($obj);
            $middle->$relatedSetter($this->object_references[$relatedClass.'_'.$value]);
            $middle->save($this->con);
        }
    }
}
