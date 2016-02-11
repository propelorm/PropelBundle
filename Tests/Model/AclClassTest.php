<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Tests\Model\Acl;

use Propel\Bundle\PropelBundle\Model\Acl\AclClass;
use Propel\Bundle\PropelBundle\Model\Acl\AclClassQuery;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Propel\Bundle\PropelBundle\Tests\AclTestCase;

/**
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class AclClassTest extends AclTestCase
{
    public function testFromAclObjectIdentity()
    {
        $type = 'Merchant';

        $aclClass = AclClass::fromAclObjectIdentity(new ObjectIdentity(5, $type), $this->con);
        $this->assertInstanceOf('Propel\Bundle\PropelBundle\Model\Acl\AclClass', $aclClass);
        $this->assertEquals($type, $aclClass->getType());

        $dbEntry = AclClassQuery::create()->findOne($this->con);
        $this->assertInstanceOf('Propel\Bundle\PropelBundle\Model\Acl\AclClass', $dbEntry);
        $this->assertEquals($type, $dbEntry->getType());

        $this->assertEquals($dbEntry->getId(), $aclClass->getId());
    }
}
