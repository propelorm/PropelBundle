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
use Propel\PropelBundle\Model\Acl\ObjectIdentity as ModelObjectIdentity;
use Propel\PropelBundle\Model\Acl\ObjectIdentityQuery;

use Propel\PropelBundle\Tests\Fixtures\Model\Book;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

/**
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class ObjectIdentityQueryTest extends TestCase
{
    public function testFilterByAclObjectIdentity()
    {
        $aclObj = new ObjectIdentity(1, 'Propel\PropelBundle\Tests\Fixtures\Model\Book');

        $aclClass = AclClass::fromAclObjectIdentity($aclObj, $this->con);
        $this->assertInstanceOf('Propel\PropelBundle\Model\Acl\AclClass', $aclClass);

        // None given.
        $result = ObjectIdentityQuery::create()->filterByAclObjectIdentity($aclObj, $this->con)->find($this->con);
        $this->assertEquals(0, count($result));

        $objIdentity = new ModelObjectIdentity();
        $this->assertTrue((bool) $objIdentity
            ->setAclClass($aclClass)
            ->setIdentifier(1)
            ->save($this->con)
        );

        $result = ObjectIdentityQuery::create()->filterByAclObjectIdentity($aclObj, $this->con)->find($this->con);
        $this->assertEquals(1, count($result));

        $this->assertEquals($aclClass->getId(), $result->getFirst()->getClassId());
        $this->assertEquals(1, $result->getFirst()->getIdentifier());

        // Change the entity.
        $aclObj = new ObjectIdentity(2, 'Propel\PropelBundle\Tests\Fixtures\Model\Book');
        $result = ObjectIdentityQuery::create()->filterByAclObjectIdentity($aclObj, $this->con)->find($this->con);
        $this->assertEquals(0, count($result));
    }

    /**
     * @depends testFilterByAclObjectIdentity
     */
    public function testFindOneByAclObjectIdentity()
    {
        $aclObj = new ObjectIdentity(1, 'Propel\PropelBundle\Tests\Fixtures\Model\Book');
        $aclClass = AclClass::fromAclObjectIdentity($aclObj, $this->con);

        $result = ObjectIdentityQuery::create()->findOneByAclObjectIdentity($aclObj, $this->con);
        $this->assertEmpty($result);

        $objIdentity = new ModelObjectIdentity();
        $this->assertTrue((bool) $objIdentity
            ->setAclClass($aclClass)
            ->setIdentifier(1)
            ->save($this->con)
        );

        $result = ObjectIdentityQuery::create()->findOneByAclObjectIdentity($aclObj, $this->con);
        $this->assertInstanceOf('Propel\PropelBundle\Model\Acl\ObjectIdentity', $result);
        $this->assertSame($objIdentity, $result);
    }

    protected function createObjectIdentities()
    {
        $aclObj = new ObjectIdentity(1, 'Propel\PropelBundle\Tests\Fixtures\Model\Book');
        $aclClass = AclClass::fromAclObjectIdentity($aclObj, $this->con);

        $objIdentity = new ModelObjectIdentity();
        $this->assertTrue((bool) $objIdentity
            ->setAclClass($aclClass)
            ->setIdentifier(1)
            ->save($this->con)
        );

        $childObjIdentity = new ModelObjectIdentity();
        $this->assertTrue((bool) $childObjIdentity
            ->setAclClass($aclClass)
            ->setIdentifier(2)
            ->save($this->con)
        );

        $grandChildObjIdentity = new ModelObjectIdentity();
        $this->assertTrue((bool) $grandChildObjIdentity
            ->setAclClass($aclClass)
            ->setIdentifier(3)
            ->save($this->con)
        );

        return array($objIdentity, $childObjIdentity, $grandChildObjIdentity);
    }

    public function testFindChildren()
    {
        list($objIdentity, $childObjIdentity) = $this->createObjectIdentities();

        // Parent not set, yet.
        $result = ObjectIdentityQuery::create()->findChildren($objIdentity, $this->con);
        $this->assertEquals(0, count($result));

        $childObjIdentity->setObjectIdentityRelatedByParentObjectIdentityId($objIdentity)->save($this->con);

        $result = ObjectIdentityQuery::create()->findChildren($objIdentity, $this->con);
        $this->assertEquals(1, count($result));
        $this->assertInstanceOf('Propel\PropelBundle\Model\Acl\ObjectIdentity', $result->getFirst());
        $this->assertSame($childObjIdentity, $result->getFirst());
        $this->assertSame($objIdentity, $result->getFirst()->getObjectIdentityRelatedByParentObjectIdentityId());
    }

    public function testFindGrandChildren()
    {
        list($objIdentity, $childObjIdentity, $grandChildObjIdentity) = $this->createObjectIdentities();

        // Parents not set, yet.
        $result = ObjectIdentityQuery::create()->findGrandChildren($objIdentity, $this->con);
        $this->assertEquals(0, count($result));

        $childObjIdentity->setObjectIdentityRelatedByParentObjectIdentityId($objIdentity)->save($this->con);

        $result = ObjectIdentityQuery::create()->findGrandChildren($objIdentity, $this->con);
        $this->assertEquals(1, count($result));

        $grandChildObjIdentity->setObjectIdentityRelatedByParentObjectIdentityId($childObjIdentity)->save($this->con);

        $result = ObjectIdentityQuery::create()->findGrandChildren($objIdentity, $this->con);
        $this->assertEquals(2, count($result));
    }

    public function testFindAncestors()
    {
        list($objIdentity, $childObjIdentity) = $this->createObjectIdentities();

        // Parents not set, yet.
        $result = ObjectIdentityQuery::create()->findAncestors($childObjIdentity, $this->con);
        $this->assertEquals(0, count($result));

        $childObjIdentity->setObjectIdentityRelatedByParentObjectIdentityId($objIdentity)->save($this->con);

        $result = ObjectIdentityQuery::create()->findAncestors($childObjIdentity, $this->con);
        $this->assertEquals(1, count($result));
    }
}