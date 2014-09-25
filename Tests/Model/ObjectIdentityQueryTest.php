<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Tests\Model\Acl;

use Propel\PropelBundle\Model\Acl\AclClass;
use Propel\PropelBundle\Model\Acl\ObjectIdentityQuery;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Propel\PropelBundle\Tests\AclTestCase;

/**
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class ObjectIdentityQueryTest extends AclTestCase
{
    public function testFilterByAclObjectIdentity()
    {
        $aclObj = new ObjectIdentity(1, 'Propel\PropelBundle\Tests\Fixtures\Model\Book');

        $aclClass = AclClass::fromAclObjectIdentity($aclObj, $this->con);
        $this->assertInstanceOf('Propel\PropelBundle\Model\Acl\AclClass', $aclClass);

        // None given.
        $result = ObjectIdentityQuery::create()->filterByAclObjectIdentity($aclObj, $this->con)->find($this->con);
        $this->assertCount(0, $result);

        $this->createModelObjectIdentity(1);

        $result = ObjectIdentityQuery::create()->filterByAclObjectIdentity($aclObj, $this->con)->find($this->con);
        $this->assertCount(1, $result);

        $this->assertEquals($aclClass->getId(), $result->getFirst()->getClassId());
        $this->assertEquals(1, $result->getFirst()->getIdentifier());

        // Change the entity.
        $aclObj = new ObjectIdentity(2, 'Propel\PropelBundle\Tests\Fixtures\Model\Book');
        $result = ObjectIdentityQuery::create()->filterByAclObjectIdentity($aclObj, $this->con)->find($this->con);
        $this->assertCount(0, $result);
    }

    /**
     * @depends testFilterByAclObjectIdentity
     */
    public function testFindOneByAclObjectIdentity()
    {
        $aclObj = new ObjectIdentity(1, 'Propel\PropelBundle\Tests\Fixtures\Model\Book');

        $result = ObjectIdentityQuery::create()->findOneByAclObjectIdentity($aclObj, $this->con);
        $this->assertEmpty($result);

        $objIdentity = $this->createModelObjectIdentity(1);

        $result = ObjectIdentityQuery::create()->findOneByAclObjectIdentity($aclObj, $this->con);
        $this->assertInstanceOf('Propel\PropelBundle\Model\Acl\ObjectIdentity', $result);
        $this->assertSame($objIdentity, $result);
    }

    /**
     * @depends testFindOneByAclObjectIdentity
     */
    public function testFindChildren()
    {
        list($objIdentity, $childObjIdentity) = $this->createObjectIdentities();

        // Parent not set, yet.
        $result = ObjectIdentityQuery::create()->findChildren($objIdentity, $this->con);
        $this->assertCount(0, $result);

        $childObjIdentity->setObjectIdentityRelatedByParentObjectIdentityId($objIdentity)->save($this->con);

        $result = ObjectIdentityQuery::create()->findChildren($objIdentity, $this->con);
        $this->assertCount(1, $result);
        $this->assertInstanceOf('Propel\PropelBundle\Model\Acl\ObjectIdentity', $result->getFirst());
        $this->assertSame($childObjIdentity, $result->getFirst());
        $this->assertSame($objIdentity, $result->getFirst()->getObjectIdentityRelatedByParentObjectIdentityId());
    }

    /**
     * @depends testFindOneByAclObjectIdentity
     */
    public function testFindGrandChildren()
    {
        list($objIdentity, $childObjIdentity, $grandChildObjIdentity) = $this->createObjectIdentities();

        // Parents not set, yet.
        $result = ObjectIdentityQuery::create()->findGrandChildren($objIdentity, $this->con);
        $this->assertCount(0, $result);

        $childObjIdentity->setObjectIdentityRelatedByParentObjectIdentityId($objIdentity)->save($this->con);

        $result = ObjectIdentityQuery::create()->findGrandChildren($objIdentity, $this->con);
        $this->assertCount(1, $result);

        $grandChildObjIdentity->setObjectIdentityRelatedByParentObjectIdentityId($childObjIdentity)->save($this->con);

        $result = ObjectIdentityQuery::create()->findGrandChildren($objIdentity, $this->con);
        $this->assertCount(2, $result);
    }

    /**
     * @depends testFindOneByAclObjectIdentity
     */
    public function testFindAncestors()
    {
        list($objIdentity, $childObjIdentity) = $this->createObjectIdentities();

        // Parents not set, yet.
        $result = ObjectIdentityQuery::create()->findAncestors($childObjIdentity, $this->con);
        $this->assertCount(0, $result);

        $childObjIdentity->setObjectIdentityRelatedByParentObjectIdentityId($objIdentity)->save($this->con);

        $result = ObjectIdentityQuery::create()->findAncestors($childObjIdentity, $this->con);
        $this->assertCount(1, $result);
    }

    protected function createObjectIdentities()
    {
        return array(
            $this->createModelObjectIdentity(1),
            $this->createModelObjectIdentity(2),
            $this->createModelObjectIdentity(3),
        );
    }
}
