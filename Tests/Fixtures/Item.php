<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Bundle\PropelBundle\Tests\Fixtures;

use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Connection\ConnectionInterface;

class Item implements ActiveRecordInterface
{
    private $id;

    private $value;

    private $groupName;

    private $price;

    public function __construct($id = null, $value = null, $groupName = null, $price = null)
    {
        $this->id = $id;
        $this->value = $value;
        $this->groupName = $groupName;
        $this->price = $price;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getGroupName()
    {
        return $this->groupName;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function getPrimaryKey()
    {
        return $this->getId();
    }

    public function setPrimaryKey($primaryKey)
    {
        $this->setId($primaryKey);
    }

    public function isModified()
    {
        return false;
    }

    public function isColumnModified($col)
    {
        return false;
    }

    public function isNew()
    {
        return false;
    }

    public function setNew($b)
    {
    }

    public function resetModified()
    {
    }

    public function isDeleted()
    {
        return false;
    }

    public function setDeleted($b)
    {
    }

    public function delete(ConnectionInterface $con = null)
    {
    }

    public function save(ConnectionInterface $con = null)
    {
    }
}
