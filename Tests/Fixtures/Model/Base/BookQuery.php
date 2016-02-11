<?php

namespace Propel\Bundle\PropelBundle\Tests\Fixtures\Model\Base;

use \Exception;
use \PDO;
use Propel\Bundle\PropelBundle\Tests\Fixtures\Model\Book as ChildBook;
use Propel\Bundle\PropelBundle\Tests\Fixtures\Model\BookQuery as ChildBookQuery;
use Propel\Bundle\PropelBundle\Tests\Fixtures\Model\Map\BookTableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveQuery\ModelJoin;
use Propel\Runtime\Collection\Collection;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;

/**
 * Base class that represents a query for the 'book' table.
 *
 *
 *
 * @method     ChildBookQuery orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildBookQuery orderByTitle($order = Criteria::ASC) Order by the title column
 * @method     ChildBookQuery orderByIsbn($order = Criteria::ASC) Order by the ISBN column
 * @method     ChildBookQuery orderByAuthorId($order = Criteria::ASC) Order by the author_id column
 *
 * @method     ChildBookQuery groupById() Group by the id column
 * @method     ChildBookQuery groupByTitle() Group by the title column
 * @method     ChildBookQuery groupByIsbn() Group by the ISBN column
 * @method     ChildBookQuery groupByAuthorId() Group by the author_id column
 *
 * @method     ChildBookQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildBookQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildBookQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildBook findOne(ConnectionInterface $con = null) Return the first ChildBook matching the query
 * @method     ChildBook findOneOrCreate(ConnectionInterface $con = null) Return the first ChildBook matching the query, or a new ChildBook object populated from the query conditions when no match is found
 *
 * @method     ChildBook findOneById(int $id) Return the first ChildBook filtered by the id column
 * @method     ChildBook findOneByTitle(string $title) Return the first ChildBook filtered by the title column
 * @method     ChildBook findOneByIsbn(string $ISBN) Return the first ChildBook filtered by the ISBN column
 * @method     ChildBook findOneByAuthorId(int $author_id) Return the first ChildBook filtered by the author_id column
 *
 * @method     array findById(int $id) Return ChildBook objects filtered by the id column
 * @method     array findByTitle(string $title) Return ChildBook objects filtered by the title column
 * @method     array findByIsbn(string $ISBN) Return ChildBook objects filtered by the ISBN column
 * @method     array findByAuthorId(int $author_id) Return ChildBook objects filtered by the author_id column
 *
 */
abstract class BookQuery extends ModelCriteria
{

    /**
     * Initializes internal state of \Acme\DemoBundle\Model\Base\BookQuery object.
     *
     * @param string $dbName     The database name
     * @param string $modelName  The phpName of a model, e.g. 'Book'
     * @param string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'default', $modelName = '\\Propel\\Bundle\\PropelBundle\\Tests\\Fixtures\\Model\\Book', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildBookQuery object.
     *
     * @param string   $modelAlias The alias of a model in the query
     * @param Criteria $criteria   Optional Criteria to build the query from
     *
     * @return ChildBookQuery
     */
    public static function create($modelAlias = null, $criteria = null)
    {
        if ($criteria instanceof \Propel\Bundle\PropelBundle\Tests\Fixtures\Model\BookQuery) {
            return $criteria;
        }
        $query = new \Propel\Bundle\PropelBundle\Tests\Fixtures\Model\BookQuery();
        if (null !== $modelAlias) {
            $query->setModelAlias($modelAlias);
        }
        if ($criteria instanceof Criteria) {
            $query->mergeWith($criteria);
        }

        return $query;
    }

    /**
     * Find object by primary key.
     * Propel uses the instance pool to skip the database if the object exists.
     * Go fast if the query is untouched.
     *
     * <code>
     * $obj  = $c->findPk(12, $con);
     * </code>
     *
     * @param mixed               $key Primary key to use for the query
     * @param ConnectionInterface $con an optional connection object
     *
     * @return ChildBook|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = BookTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(BookTableMap::DATABASE_NAME);
        }
        $this->basePreSelect($con);
        if ($this->formatter || $this->modelAlias || $this->with || $this->select
         || $this->selectColumns || $this->asColumns || $this->selectModifiers
         || $this->map || $this->having || $this->joins) {
            return $this->findPkComplex($key, $con);
        } else {
            return $this->findPkSimple($key, $con);
        }
    }

    /**
     * Find object by primary key using raw SQL to go fast.
     * Bypass doSelect() and the object formatter by using generated code.
     *
     * @param mixed               $key Primary key to use for the query
     * @param ConnectionInterface $con A connection object
     *
     * @return ChildBook A model object, or null if the key is not found
     */
    protected function findPkSimple($key, $con)
    {
        $sql = 'SELECT ID, TITLE, ISBN, AUTHOR_ID FROM book WHERE ID = :p0';
        try {
            $stmt = $con->prepare($sql);
            $stmt->bindValue(':p0', $key, PDO::PARAM_INT);
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute SELECT statement [%s]', $sql), 0, $e);
        }
        $obj = null;
        if ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            $obj = new ChildBook();
            $obj->hydrate($row);
            BookTableMap::addInstanceToPool($obj, (string) $key);
        }
        $stmt->closeCursor();

        return $obj;
    }

    /**
     * Find object by primary key.
     *
     * @param mixed               $key Primary key to use for the query
     * @param ConnectionInterface $con A connection object
     *
     * @return ChildBook|array|mixed the result, formatted by the current formatter
     */
    protected function findPkComplex($key, $con)
    {
        // As the query uses a PK condition, no limit(1) is necessary.
        $criteria = $this->isKeepQuery() ? clone $this : $this;
        $dataFetcher = $criteria
            ->filterByPrimaryKey($key)
            ->doSelect($con);

        return $criteria->getFormatter()->init($criteria)->formatOne($dataFetcher);
    }

    /**
     * Find objects by primary key
     * <code>
     * $objs = $c->findPks(array(12, 56, 832), $con);
     * </code>
     * @param array               $keys Primary keys to use for the query
     * @param ConnectionInterface $con  an optional connection object
     *
     * @return ObjectCollection|array|mixed the list of results, formatted by the current formatter
     */
    public function findPks($keys, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getReadConnection($this->getDbName());
        }
        $this->basePreSelect($con);
        $criteria = $this->isKeepQuery() ? clone $this : $this;
        $dataFetcher = $criteria
            ->filterByPrimaryKeys($keys)
            ->doSelect($con);

        return $criteria->getFormatter()->init($criteria)->format($dataFetcher);
    }

    /**
     * Filter the query by primary key
     *
     * @param mixed $key Primary key to use for the query
     *
     * @return ChildBookQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {
        return $this->addUsingAlias(BookTableMap::ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param array $keys The list of primary key to use for the query
     *
     * @return ChildBookQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {
        return $this->addUsingAlias(BookTableMap::ID, $keys, Criteria::IN);
    }

    /**
     * Filter the query on the id column
     *
     * Example usage:
     * <code>
     * $query->filterById(1234); // WHERE id = 1234
     * $query->filterById(array(12, 34)); // WHERE id IN (12, 34)
     * $query->filterById(array('min' => 12)); // WHERE id > 12
     * </code>
     *
     * @param mixed  $id         The value to use as filter.
     *                           Use scalar values for equality.
     *                           Use array values for in_array() equivalent.
     *                           Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookQuery The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(BookTableMap::ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(BookTableMap::ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(BookTableMap::ID, $id, $comparison);
    }

    /**
     * Filter the query on the title column
     *
     * Example usage:
     * <code>
     * $query->filterByTitle('fooValue');   // WHERE title = 'fooValue'
     * $query->filterByTitle('%fooValue%'); // WHERE title LIKE '%fooValue%'
     * </code>
     *
     * @param string $title      The value to use as filter.
     *                           Accepts wildcards (* and % trigger a LIKE)
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookQuery The current query, for fluid interface
     */
    public function filterByTitle($title = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($title)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $title)) {
                $title = str_replace('*', '%', $title);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(BookTableMap::TITLE, $title, $comparison);
    }

    /**
     * Filter the query on the ISBN column
     *
     * Example usage:
     * <code>
     * $query->filterByIsbn('fooValue');   // WHERE ISBN = 'fooValue'
     * $query->filterByIsbn('%fooValue%'); // WHERE ISBN LIKE '%fooValue%'
     * </code>
     *
     * @param string $isbn       The value to use as filter.
     *                           Accepts wildcards (* and % trigger a LIKE)
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookQuery The current query, for fluid interface
     */
    public function filterByIsbn($isbn = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($isbn)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $isbn)) {
                $isbn = str_replace('*', '%', $isbn);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(BookTableMap::ISBN, $isbn, $comparison);
    }

    /**
     * Filter the query on the author_id column
     *
     * Example usage:
     * <code>
     * $query->filterByAuthorId(1234); // WHERE author_id = 1234
     * $query->filterByAuthorId(array(12, 34)); // WHERE author_id IN (12, 34)
     * $query->filterByAuthorId(array('min' => 12)); // WHERE author_id > 12
     * </code>
     *
     * @see       filterByAuthor()
     *
     * @param mixed  $authorId   The value to use as filter.
     *                           Use scalar values for equality.
     *                           Use array values for in_array() equivalent.
     *                           Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookQuery The current query, for fluid interface
     */
    public function filterByAuthorId($authorId = null, $comparison = null)
    {
        if (is_array($authorId)) {
            $useMinMax = false;
            if (isset($authorId['min'])) {
                $this->addUsingAlias(BookTableMap::AUTHOR_ID, $authorId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($authorId['max'])) {
                $this->addUsingAlias(BookTableMap::AUTHOR_ID, $authorId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(BookTableMap::AUTHOR_ID, $authorId, $comparison);
    }

    /**
     * Adds a JOIN clause to the query using the Author relation
     *
     * @param string $relationAlias optional alias for the relation
     * @param string $joinType      Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return ChildBookQuery The current query, for fluid interface
     */
    public function joinAuthor($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('Author');

        // create a ModelJoin object for this join
        $join = new ModelJoin();
        $join->setJoinType($joinType);
        $join->setRelationMap($relationMap, $this->useAliasInSQL ? $this->getModelAlias() : null, $relationAlias);
        if ($previousJoin = $this->getPreviousJoin()) {
            $join->setPreviousJoin($previousJoin);
        }

        // add the ModelJoin to the current object
        if ($relationAlias) {
            $this->addAlias($relationAlias, $relationMap->getRightTable()->getName());
            $this->addJoinObject($join, $relationAlias);
        } else {
            $this->addJoinObject($join, 'Author');
        }

        return $this;
    }

    /**
     * Exclude object from result
     *
     * @param ChildBook $book Object to remove from the list of results
     *
     * @return ChildBookQuery The current query, for fluid interface
     */
    public function prune($book = null)
    {
        if ($book) {
            $this->addUsingAlias(BookTableMap::ID, $book->getId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the book table.
     *
     * @param  ConnectionInterface $con the connection to use
     * @return int                 The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(BookTableMap::DATABASE_NAME);
        }
        $affectedRows = 0; // initialize var to track total num of affected rows
        try {
            // use transaction because $criteria could contain info
            // for more than one table or we could emulating ON DELETE CASCADE, etc.
            $con->beginTransaction();
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            BookTableMap::clearInstancePool();
            BookTableMap::clearRelatedInstancePool();

            $con->commit();
        } catch (PropelException $e) {
            $con->rollBack();
            throw $e;
        }

        return $affectedRows;
    }

    /**
     * Performs a DELETE on the database, given a ChildBook or Criteria object OR a primary key value.
     *
     * @param  mixed               $values Criteria or ChildBook object or primary key or array of primary keys
     *                                     which is used to create the DELETE statement
     * @param  ConnectionInterface $con    the connection to use
     * @return int                 The number of affected rows (if supported by underlying database driver).  This includes CASCADE-related rows
     *                                    if supported by native driver or if emulated using Propel.
     * @throws PropelException     Any exceptions caught during processing will be
     *                                    rethrown wrapped into a PropelException.
     */
     public function delete(ConnectionInterface $con = null)
     {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(BookTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(BookTableMap::DATABASE_NAME);

        $affectedRows = 0; // initialize var to track total num of affected rows

        try {
            // use transaction because $criteria could contain info
            // for more than one table or we could emulating ON DELETE CASCADE, etc.
            $con->beginTransaction();

        BookTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            BookTableMap::clearRelatedInstancePool();
            $con->commit();

            return $affectedRows;
        } catch (PropelException $e) {
            $con->rollBack();
            throw $e;
        }
    }

} // BookQuery
