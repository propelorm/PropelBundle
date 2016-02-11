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

use Propel\Runtime\Map\ColumnMap;
use Propel\Runtime\Map\TableMap;

class ReadOnlyItemQuery
{
    public function getTableMap()
    {
        // Allows to define methods in this class
        // to avoid a lot of mock classes
        return $this;
    }

    public function getPrimaryKeys()
    {
        $cm = new ColumnMap('id', new TableMap());
        $cm->setType('INTEGER');

        return array('id' => $cm);
    }
}
