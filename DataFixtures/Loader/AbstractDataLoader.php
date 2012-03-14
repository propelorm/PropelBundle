<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\DataFixtures\Loader;

use \Propel;
use \BasePeer;
use \BaseObject;
use \ColumnMap;
use \PropelException;

use Propel\PropelBundle\DataFixtures\AbstractDataHandler;
use Propel\PropelBundle\Util\PropelInflector;

use Symfony\Component\Finder\Finder;

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
    private $deletedClasses;
    /**
     * @var array
     */
    private $object_references;

    /**
     * Default constructor
     *
     * @param string $rootDir   The root directory.
     */
    public function __construct($rootDir)
    {
        parent::__construct($rootDir);

        $this->deletedClasses = array();
        $this->object_references = array();
    }

    /**
     * Transforms a file containing data in an array.
     *
     * @param string $file  A filename.
     * @return array
     */
    abstract protected function transformDataToArray($file);

    /**
     * {@inheritdoc}
     */
    public function load($files = array(), $connectionName)
    {
        $nbFiles = 0;

        $this->loadMapBuilders($connectionName);
        $this->con = Propel::getConnection($connectionName);

        try {
            $this->con->beginTransaction();

            foreach ($files as $file) {
                $datas = $this->transformDataToArray($file);

                if (count($datas) > 0) {
                    $this->deleteCurrentData($datas);
                    $this->loadDataFromArray($datas);
                    $nbFiles++;
                }
            }

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
     * @param array   $data  The data to delete
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

                // Check that peer class exists before calling doDeleteAll()
                $peerClass = constant($class.'::PEER');
                if (!class_exists($peerClass)) {
                    throw new \InvalidArgumentException(sprintf('Unknown class "%sPeer".', $class));
                }

                // bypass the soft_delete behavior if enabled
                $deleteMethod = method_exists($peerClass, 'doForceDeleteAll') ? 'doForceDeleteAll' : 'doDeleteAll';
                call_user_func(array($peerClass, $deleteMethod), $this->con);

                $this->deletedClasses[] = $class;
            }
        }
    }

    /**
     * Loads the data using the generated data model.
     *
     * @param array   $data  The data to be loaded
     */
    protected function loadDataFromArray($data = null)
    {
        if ($data === null) {
            return;
        }

        foreach ($data as $class => $datas) {
            $class        = trim($class);
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
                    try {
                        if (is_array($value) && 's' === substr($name, -1)) {
                            // many to many relationship
                            $this->loadManyToMany($obj, substr($name, 0, -1), $value);
                            continue;
                        }
                    } catch (\PropelException $e) {
                        // Check whether this is actually an array stored in the object.
                        if ('Cannot fetch TableMap for undefined table: '.substr($name, 0, -1) === $e->getMessage()) {
                            if ('ARRAY' !== $tableMap->getColumn($name)->getType()) {
                                throw $e;
                            }
                        }
                    }

                    $isARealColumn = true;
                    if ($tableMap->hasColumn($name)) {
                        $column = $tableMap->getColumn($name);
                    } else if ($tableMap->hasColumnByPhpName($name)) {
                        $column = $tableMap->getColumnByPhpName($name);
                    } else {
                        $isARealColumn = false;
                    }

                    // foreign key?
                    if ($isARealColumn) {
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
                    }
                    elseif (is_callable(array($obj, $method = 'set'.ucfirst(PropelInflector::camelize($name))))) {
                        $obj->$method($value);
                    } else {
                        throw new \InvalidArgumentException(sprintf('Column "%s" does not exist for class "%s".', $name, $class));
                    }
                }

                $obj->save($this->con);

                // save the object for future reference
                if (method_exists($obj, 'getPrimaryKey')) {
                    $this->object_references[$class.'_'.$key] = $obj;
                }
            }
        }
    }

    /**
     * Loads many to many objects.
     *
     * @param BaseObject $obj           A Propel object
     * @param string $middleTableName   The middle table name
     * @param array $values             An array of values
     */
    protected function loadManyToMany($obj, $middleTableName, $values)
    {
        $middleTable = $this->dbMap->getTable($middleTableName);
        $middleClass = $middleTable->getPhpName();

        foreach ($middleTable->getColumns() as $column) {
            if ($column->isForeignKey() && constant(constant(get_class($obj).'::PEER').'::TABLE_NAME') != $column->getRelatedTableName()) {
                $relatedClass = $this->dbMap->getTable($column->getRelatedTableName())->getPhpName();
                break;
            }
        }

        if (!isset($relatedClass)) {
            throw new \InvalidArgumentException(sprintf('Unable to find the many-to-many relationship for object "%s".', get_class($obj)));
        }

        $setter = 'set'.get_class($obj);
        $relatedSetter = 'set'.$relatedClass;

        foreach ($values as $value) {
            if (!isset($this->object_references[$relatedClass.'_'.$value])) {
                throw new \InvalidArgumentException(
                    sprintf('The object "%s" from class "%s" is not defined in your data file.', $value, $relatedClass)
                );
            }

            $middle = new $middleClass();
            $middle->$setter($obj);
            $middle->$relatedSetter($this->object_references[$relatedClass.'_'.$value]);
            $middle->save();
        }
    }
}
