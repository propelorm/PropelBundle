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

class ReadOnlyItem implements ActiveRecordInterface
{
    public function getName()
    {
        return 'Marvin';
    }

    public function getPrimaryKey()
    {
        return 42;
    }
}
