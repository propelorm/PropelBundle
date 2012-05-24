<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Tests\Security\Acl;

use Propel\PropelBundle\Model\Acl\SecurityIdentity;
use Propel\PropelBundle\Model\Acl\EntryQuery;
use Propel\PropelBundle\Model\Acl\EntryPeer;

use Propel\PropelBundle\Security\Acl\AclProvider;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\PermissionGrantingStrategy;

use Propel\PropelBundle\Tests\AclTestCase;
use Propel\PropelBundle\Tests\Fixtures\Acl\ArrayCache as AclCache;

/**
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class AclProviderTest extends AclTestCase
{
    public function testFindAclNoneGiven()
    {
        $provider = $this->getAclProvider();

        $this->setExpectedException('Symfony\Component\Security\Acl\Exception\AclNotFoundException', 'There is no ACL available for this object identity. Please create one using the MutableAclProvider.');
        $provider->findAcl($this->getAclObjectIdentity());
    }

    public function testFindAclNoneGivenFilterSecurityIdentity()
    {
        $provider = $this->getAclProvider();

        $this->setExpectedException('Symfony\Component\Security\Acl\Exception\AclNotFoundException', 'There is at least no ACL for this object identity and the given security identities. Try retrieving the ACL without security identity filter and add ACEs for the security identities.');
        $provider->findAcl($this->getAclObjectIdentity(), array($this->getRoleSecurityIdentity()));
    }

    public function testFindAclWithEntries()
    {
        $obj = $this->createModelObjectIdentity(1);
        $entry = $this->createEntry();
        $entry
            ->setSecurityIdentity(SecurityIdentity::fromAclIdentity($this->getRoleSecurityIdentity('ROLE_USER')))
            ->setAclClass($obj->getAclClass())
            ->setMask(64)
        ;
        $obj->addEntry($entry)->save($this->con);

        $acl = $this->getAclProvider()->findAcl($this->getAclObjectIdentity(1), array($this->getRoleSecurityIdentity('ROLE_USER')));

        $this->assertNotEmpty($acl);
        $this->assertInstanceOf('Propel\PropelBundle\Security\Acl\Domain\Acl', $acl);

        $this->assertEmpty($acl->getFields());
        $this->assertEmpty($acl->getClassAces());
        $this->assertNotEmpty($acl->getObjectAces());
        $this->assertCount(1, $acl->getObjectAces());

        $this->assertNull($acl->getParentAcl());
        $this->assertTrue($acl->isEntriesInheriting());

        $this->assertFalse($acl->isSidLoaded($this->getRoleSecurityIdentity('ROLE_ADMIN')));
        $this->assertTrue($acl->isSidLoaded($this->getRoleSecurityIdentity('ROLE_USER')));

        $this->assertTrue($acl->isGranted(array(1, 2, 4, 8, 16, 32, 64), array($this->getRoleSecurityIdentity('ROLE_USER'))));

        $this->setExpectedException('Symfony\Component\Security\Acl\Exception\NoAceFoundException');
        $acl->isGranted(array(128), array($this->getRoleSecurityIdentity('ROLE_USER')));
    }

    /**
     * @depends testFindAclWithEntries
     */
    public function testFindAclWithParent()
    {
        $parent = $this->createModelObjectIdentity(1);
        $entry = $this->createEntry();
        $entry
            ->setSecurityIdentity(SecurityIdentity::fromAclIdentity($this->getRoleSecurityIdentity('ROLE_USER')))
            ->setAclClass($parent->getAclClass())
            ->setMask(128)
        ;
        $parent->addEntry($entry)->save($this->con);

        $obj = $this->createModelObjectIdentity(2);
        $obj->setObjectIdentityRelatedByParentObjectIdentityId($parent);

        $entry = $this->createEntry();
        $entry
            ->setSecurityIdentity(SecurityIdentity::fromAclIdentity($this->getRoleSecurityIdentity('ROLE_USER')))
            ->setAclClass($obj->getAclClass())
            ->setMask(64)
        ;
        $obj->addEntry($entry)->save($this->con);

        $acl = $this->getAclProvider()->findAcl($this->getAclObjectIdentity(2), array($this->getRoleSecurityIdentity('ROLE_USER')));
        $parent = $acl->getParentAcl();

        $this->assertInstanceOf('Propel\PropelBundle\Security\Acl\Domain\Acl', $acl);
        $this->assertInstanceOf('Propel\PropelBundle\Security\Acl\Domain\Acl', $parent);

        $aces = $acl->getObjectAces();
        $parentAces = $parent->getObjectAces();
        $this->assertEquals(64, $aces[0]->getMask());
        $this->assertEquals(128, $parentAces[0]->getMask());
    }

    /**
     * @depends testFindAclWithEntries
     */
    public function testFindAcls()
    {
        $obj = $this->createModelObjectIdentity(1);
        $entry = $this->createEntry();
        $entry
            ->setSecurityIdentity(SecurityIdentity::fromAclIdentity($this->getRoleSecurityIdentity('ROLE_USER')))
            ->setAclClass($obj->getAclClass())
        ;
        $obj->addEntry($entry)->save($this->con);

        $aclObj = $this->getAclObjectIdentity(1);

        $acls = $this->getAclProvider()->findAcls(array($aclObj), array($this->getRoleSecurityIdentity('ROLE_USER')));
        $acl = $this->getAclProvider()->findAcl($aclObj, array($this->getRoleSecurityIdentity('ROLE_USER')));

        $this->assertNotEmpty($acls);
        $this->assertCount(1, $acls);
        $this->assertTrue($acls->contains($aclObj));
        $this->assertEquals($acl, $acls[$aclObj]);
    }

    public function testFindChildrenParentNotExists()
    {
        $this->assertEmpty($this->getAclProvider()->findChildren(new ObjectIdentity(5, 'Book')));
    }

    /**
     * @depends testFindAclWithEntries
     */
    public function testFindChildrenWithoutChildren()
    {
        $obj = $this->createModelObjectIdentity(1);
        $entry = $this->createEntry();
        $entry
            ->setSecurityIdentity(SecurityIdentity::fromAclIdentity($this->getRoleSecurityIdentity('ROLE_USER')))
            ->setAclClass($obj->getAclClass())
            ->setMask(64)
        ;
        $obj->addEntry($entry)->save($this->con);

        $childrenAcl = $this->getAclProvider()->findChildren($this->getAclObjectIdentity(1));
        $this->assertEmpty($childrenAcl);
    }

    public function testFindChildrenDirectOnly()
    {
        list($parentObj, $obj, $childObj) = $this->createObjectIdentities();

        $obj->setObjectIdentityRelatedByParentObjectIdentityId($parentObj)->save($this->con);
        $childObj->setObjectIdentityRelatedByParentObjectIdentityId($obj)->save($this->con);

        $children = $this->getAclProvider()->findChildren($this->getAclObjectIdentity(1), true);

        $this->assertNotEmpty($children);
        $this->assertCount(1, $children);
        $this->assertEquals(2, $children[0]->getIdentifier());
    }

    public function testFindChildrenWithGrandChildren()
    {
        list($parentObj, $obj, $childObj) = $this->createObjectIdentities();

        $obj->setObjectIdentityRelatedByParentObjectIdentityId($parentObj)->save($this->con);
        $childObj->setObjectIdentityRelatedByParentObjectIdentityId($obj)->save($this->con);

        $children = $this->getAclProvider()->findChildren($this->getAclObjectIdentity(1));

        $this->assertNotEmpty($children);
        $this->assertCount(2, $children);
        $this->assertEquals(2, $children[0]->getIdentifier());
        $this->assertEquals(3, $children[1]->getIdentifier());
    }

    protected function createObjectIdentities()
    {
        $parentObj = $this->createModelObjectIdentity(1);
        $entry = $this->createEntry();
        $entry
            ->setSecurityIdentity(SecurityIdentity::fromAclIdentity($this->getRoleSecurityIdentity('ROLE_USER')))
            ->setAclClass($parentObj->getAclClass())
            ->setMask(64)
        ;
        $parentObj->addEntry($entry)->save($this->con);

        $obj = $this->createModelObjectIdentity(2);
        $entry = $this->createEntry();
        $entry
            ->setSecurityIdentity(SecurityIdentity::fromAclIdentity($this->getRoleSecurityIdentity('ROLE_USER')))
            ->setAclClass($obj->getAclClass())
            ->setMask(64)
        ;
        $obj->addEntry($entry)->save($this->con);

        $childObj = $this->createModelObjectIdentity(3);
        $entry = $this->createEntry();
        $entry
            ->setSecurityIdentity(SecurityIdentity::fromAclIdentity($this->getRoleSecurityIdentity('ROLE_USER')))
            ->setAclClass($childObj->getAclClass())
            ->setMask(64)
        ;
        $childObj->addEntry($entry)->save($this->con);

        return array($parentObj, $obj, $childObj);
    }

    /**
     * @depends testFindAclWithEntries
     */
    public function testFindAclReadsFromCache()
    {
        $this->cache = new AclCache();

        $obj = $this->createModelObjectIdentity(1);
        $entry = $this->createEntry();
        $entry
            ->setSecurityIdentity(SecurityIdentity::fromAclIdentity($this->getRoleSecurityIdentity('ROLE_USER')))
            ->setAclClass($obj->getAclClass())
            ->setMask(64)
        ;
        $obj->addEntry($entry)->save($this->con);

        // Read and put into cache
        $acl = $this->getAclProvider()->findAcl($this->getAclObjectIdentity(1), array($this->getRoleSecurityIdentity('ROLE_USER')));
        $this->cache->content[1] = $acl;

        // Change database
        EntryQuery::create()->update(array(EntryPeer::translateFieldName(EntryPeer::MASK, \BasePeer::TYPE_COLNAME, \BasePeer::TYPE_PHPNAME) => 128), $this->con);
        $this->assertEquals(0, EntryQuery::create()->filterByMask(64)->count($this->con));

        // Verify cache has been read
        $cachedAcl = $this->getAclProvider()->findAcl($this->getAclObjectIdentity(1), array($this->getRoleSecurityIdentity('ROLE_USER')));
        $cachedObjectAces = $cachedAcl->getObjectAces();
        $this->assertSame($acl, $cachedAcl);
        $this->assertEquals(64, $cachedObjectAces[0]->getMask());
    }

    protected function getAclProvider()
    {
        return new AclProvider(new PermissionGrantingStrategy(), $this->con, $this->cache);
    }
}
