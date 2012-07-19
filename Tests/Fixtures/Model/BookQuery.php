<?php

namespace Propel\PropelBundle\Tests\Fixtures\Model;

use Propel\PropelBundle\Tests\Fixtures\Model\om\BaseBookQuery;

/**
 * Skeleton subclass for performing query and update operations on the 'book' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class BookQuery extends BaseBookQuery
{
    private $bySlug = false;
    private $byAuthorSlug = false;

    /**
     * fake for test
     */
    public function findPk($key, $con = null)
    {
        if (1 === $key) {
            $book = new Book();
            $book->setId(1);

            return $book;
        }

        return null;
    }

    /**
     * fake for test
     */
    public function filterByAuthorSlug($slug = null, $comparison = null)
    {
        if ('my-author' === $slug) {
            $this->byAuthorSlug = true;
        }

        return $this;
    }

    /**
     * fake for test
     */
    public function filterBySlug($slug = null, $comparison = null)
    {
        if ('my-book' == $slug) {
            $this->bySlug = true;
        }

        return $this;
    }

    /**
     * fake for test
     */
    public function filterByName($name = null, $comparison = null)
    {
        throw new \Exception('Test should never call this method');
    }

    /**
     * fake for test
     */
    public function findOne($con = null)
    {
        if (true === $this->bySlug) {
            $book = new Book();
            $book->setId(1);
            $book->setName('My Book');
            $book->setSlug('my-book');

            return $book;
        } elseif (true === $this->byAuthorSlug) {
            $book = new Book();
            $book->setId(2);
            $book->setName('My Kewl Book');
            $book->setSlug('my-kewl-book');

            return $book;
        }

        return null;
    }
} // BookQuery
