<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Tests\Model\Acl;

use Criteria;

use Propel\PropelBundle\Model\Acl\AclClass;
use Propel\PropelBundle\Model\Acl\AclClassPeer;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Propel\PropelBundle\Tests\AclTestCase;

/**
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class AclClassTest extends AclTestCase
{
    public function testFromAclObjectIdentity()
    {
        $type = 'Merchant';

        $aclClass = AclClass::fromAclObjectIdentity(new ObjectIdentity(5, $type), $this->con);
        $this->assertInstanceOf('Propel\PropelBundle\Model\Acl\AclClass', $aclClass);
        $this->assertEquals($type, $aclClass->getType());

        $dbEntry = AclClassPeer::doSelectOne(new Criteria(), $this->con);
        $this->assertInstanceOf('Propel\PropelBundle\Model\Acl\AclClass', $dbEntry);
        $this->assertEquals($type, $dbEntry->getType());

        $this->assertEquals($dbEntry->getId(), $aclClass->getId());
    }
}
