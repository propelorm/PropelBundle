<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Tests\Security\Acl;

use Propel\PropelBundle\Model\Acl\EntryQuery;
use Propel\PropelBundle\Model\Acl\ObjectIdentityQuery;

use Propel\PropelBundle\Tests\AclTestCase;
use Propel\PropelBundle\Tests\Fixtures\Acl\ArrayCache as AclCache;

/**
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class MutableAclProviderTest extends AclTestCase
{
    public function testCreateAcl()
    {
        $acl = $this->getAclProvider()->createAcl($this->getAclObjectIdentity(1));

        $this->assertNotEmpty($acl);
        $this->assertInstanceOf('Propel\PropelBundle\Security\Acl\Domain\MutableAcl', $acl);
        $this->assertEquals(1, $acl->getId());

        $this->assertEmpty($acl->getClassAces());
        $this->assertEmpty($acl->getObjectAces());
        $this->assertEmpty($acl->getFields());
    }

    /**
     * @depends testCreateAcl
     */
    public function testUpdateAclCreatesInsertedAces()
    {
        $acl = $this->getAclProvider()->createAcl($this->getAclObjectIdentity(1));
        $acl->insertObjectAce($this->getRoleSecurityIdentity(), 64);
        $acl->insertClassFieldAce('name', $this->getRoleSecurityIdentity('ROLE_ADMIN'), 128);

        $this->assertCount(1, $acl->getObjectAces());
        $this->assertEquals(array('name'), $acl->getFields());
        $this->assertCount(1, $acl->getClassFieldAces('name'));

        $this->assertEquals(0, EntryQuery::create()->count($this->con));
        $this->assertTrue($this->getAclProvider()->updateAcl($acl));
        $this->assertEquals(2, EntryQuery::create()->count($this->con));

        $acl = $this->getAclProvider()->findAcl($this->getAclObjectIdentity(1));
        $this->assertInstanceOf('Propel\PropelBundle\Security\Acl\Domain\MutableAcl', $acl);

        $objAces = $acl->getObjectAces();
        $this->assertCount(1, $objAces);

        $entry = $objAces[0];
        $this->assertInstanceOf('Propel\PropelBundle\Security\Acl\Domain\Entry', $entry);
        $this->assertEquals(64, $entry->getMask());
        $this->assertEquals($this->getRoleSecurityIdentity(), $entry->getSecurityIdentity());

        $classFieldAces = $acl->getClassFieldAces('name');
        $this->assertCount(1, $classFieldAces);

        $entry = $classFieldAces[0];
        $this->assertInstanceOf('Propel\PropelBundle\Security\Acl\Domain\FieldEntry', $entry);
        $this->assertEquals('name', $entry->getField());
        $this->assertEquals(128, $entry->getMask());
        $this->assertEquals($this->getRoleSecurityIdentity('ROLE_ADMIN'), $entry->getSecurityIdentity());
    }

    /**
     * @depends testUpdateAclCreatesInsertedAces
     */
    public function testCreateAclAlreadyExists()
    {
        $acl = $this->getAclProvider()->createAcl($this->getAclObjectIdentity(1));
        $acl->insertObjectAce($this->getRoleSecurityIdentity(), 64);
        $this->getAclProvider()->updateAcl($acl);

        $this->setExpectedException('Symfony\Component\Security\Acl\Exception\AclAlreadyExistsException');
        $this->getAclProvider()->createAcl($this->getAclObjectIdentity(1));
    }

    /**
     * @depends testUpdateAclCreatesInsertedAces
     */
    public function testCreateAclWithParent()
    {
        $parentAcl = $this->getAclProvider()->createAcl($this->getAclObjectIdentity(1));
        $parentAcl->insertObjectAce($this->getRoleSecurityIdentity(), 64);
        $this->getAclProvider()->updateAcl($parentAcl);

        $acl = $this->getAclProvider()->createAcl($this->getAclObjectIdentity(2));
        $acl->insertObjectAce($this->getRoleSecurityIdentity(), 128);
        $acl->setParentAcl($parentAcl);
        $this->getAclProvider()->updateAcl($acl);

        $entries = ObjectIdentityQuery::create()->orderById(\Criteria::ASC)->find($this->con);
        $this->assertCount(2, $entries);
        $this->assertNull($entries[0]->getParentObjectIdentityId());
        $this->assertEquals($entries[0]->getId(), $entries[1]->getParentObjectIdentityId());
    }

    public function testUpdateAclInvalidAcl()
    {
        $acl = $this->getMock('Symfony\Component\Security\Acl\Model\MutableAclInterface');

        $this->setExpectedException('InvalidArgumentException');
        $this->getAclProvider()->updateAcl($acl);
    }

    /**
     * @depends testUpdateAclCreatesInsertedAces
     */
    public function testUpdateAclRemovesDeletedEntries()
    {
        $acl = $this->getAclProvider()->createAcl($this->getAclObjectIdentity(1));

        $acl->insertObjectFieldAce('name', $this->getRoleSecurityIdentity(), 4);
        $acl->insertObjectFieldAce('slug', $this->getRoleSecurityIdentity(), 1);
        $this->getAclProvider()->updateAcl($acl);
        $this->assertEquals(2, EntryQuery::create()->count($this->con));

        $acl->deleteObjectFieldAce(0, 'slug');
        $this->getAclProvider()->updateAcl($acl);
        $this->assertEquals(1, EntryQuery::create()->count($this->con));

        $entry = EntryQuery::create()->findOne($this->con);
        $this->assertEquals('name', $entry->getFieldName());
        $this->assertEquals(4, $entry->getMask());
    }

    /**
     * @depends testUpdateAclCreatesInsertedAces
     */
    public function testUpdateAclCreatesMultipleAces()
    {
        $acl = $this->getAclProvider()->createAcl($this->getAclObjectIdentity(1));

        $acl->insertObjectFieldAce('name', $this->getRoleSecurityIdentity(), 16, 0, true, 'all');
        $acl->insertObjectFieldAce('name', $this->getRoleSecurityIdentity(), 4);
        $acl->insertObjectFieldAce('slug', $this->getRoleSecurityIdentity(), 1);
        $this->assertCount(2, $acl->getObjectFieldAces('name'));

        $this->getAclProvider()->updateAcl($acl);

        $entries = EntryQuery::create()->orderByMask(\Criteria::ASC)->find($this->con);
        $this->assertCount(3, $entries);

        $slugAce = $entries[0];

        $this->assertEquals('slug', $slugAce->getFieldName());
        $this->assertEquals(1, $slugAce->getMask());

        $nameRead = $entries[1];
        $this->assertEquals('name', $nameRead->getFieldName());
        $this->assertEquals(0, $nameRead->getAceOrder());
        $this->assertEquals(4, $nameRead->getMask());
        $this->assertEquals('all', $nameRead->getGrantingStrategy());

        $nameUndelete = $entries[2];
        $this->assertEquals('name', $nameUndelete->getFieldName());
        $this->assertEquals(1, $nameUndelete->getAceOrder());
        $this->assertEquals(16, $nameUndelete->getMask());
        $this->assertEquals('all', $nameUndelete->getGrantingStrategy());
    }

    /**
     * @depends testUpdateAclCreatesInsertedAces
     */
    public function testUpdateAclReadsExistingAce()
    {
        $acl = $this->getAclProvider()->createAcl($this->getAclObjectIdentity(1));
        $acl->insertObjectAce($this->getRoleSecurityIdentity(), 64);
        $this->getAclProvider()->updateAcl($acl);

        $entry = EntryQuery::create()->findOne($this->con);

        $acl = $this->getAclProvider()->findAcl($this->getAclObjectIdentity(1));
        $acl->updateObjectAce(0, 128);
        $this->getAclProvider()->updateAcl($acl);

        $updatedEntry = clone $entry;
        $updatedEntry->reload(false, $this->con);

        $this->assertEquals($entry->getId(), $updatedEntry->getId());
        $this->assertEquals(128, $updatedEntry->getMask());
    }

    public function testDeleteAclNotExisting()
    {
        $this->assertTrue($this->getAclProvider()->deleteAcl($this->getAclObjectIdentity()));
    }

    /**
     * @depends testUpdateAclCreatesInsertedAces
     */
    public function testDeleteAcl()
    {
        $aclObj = $this->getAclObjectIdentity(1);
        $acl = $this->getAclProvider()->createAcl($aclObj);
        $acl->insertObjectAce($this->getRoleSecurityIdentity(), 64);
        $acl->insertClassFieldAce('name', $this->getRoleSecurityIdentity('ROLE_ADMIN'), 128);

        $this->assertTrue($this->getAclProvider()->deleteAcl($aclObj));
        $this->assertEquals(0, ObjectIdentityQuery::create()->count($this->con));
        $this->assertEquals(0, EntryQuery::create()->count($this->con));
    }

    /**
     * @depends testCreateAclWithParent
     */
    public function testDeleteAclRemovesChildAcl()
    {
        $parentAcl = $this->getAclProvider()->createAcl($this->getAclObjectIdentity(1));
        $parentAcl->insertObjectAce($this->getRoleSecurityIdentity(), 64);
        $this->getAclProvider()->updateAcl($parentAcl);

        $acl = $this->getAclProvider()->createAcl($this->getAclObjectIdentity(2));
        $acl->insertObjectAce($this->getRoleSecurityIdentity(), 128);
        $acl->setParentAcl($parentAcl);
        $this->getAclProvider()->updateAcl($acl);

        $this->getAclProvider()->deleteAcl($this->getAclObjectIdentity(1));

        $this->assertEquals(0, ObjectIdentityQuery::create()->count($this->con));
    }

    /**
     * @depends testDeleteAcl
     */
    public function testDeleteAclRemovesClassEntriesIfLastObject()
    {
        $acl = $this->getAclProvider()->createAcl($this->getAclObjectIdentity(1));
        $acl->insertClassAce($this->getRoleSecurityIdentity(), 128);
        $this->getAclProvider()->updateAcl($acl);

        $this->getAclProvider()->deleteAcl($this->getAclObjectIdentity(1));
        $this->assertEquals(0, EntryQuery::create()->count($this->con));
    }

    /**
     * @depends testUpdateAclCreatesInsertedAces
     */
    public function testUpdateAclWritesCacheOfNewAcl()
    {
        $this->cache = new AclCache();
        $this->assertEmpty($this->cache->content);

        $acl = $this->getAcl();

        $this->assertNotEmpty($this->cache->content);
        $this->assertSame($acl, $this->cache->content[$acl->getId()]);
    }

    /**
     * @depends testUpdateAclWritesCacheOfNewAcl
     */
    public function testUpdateAclUpdatesCacheOfAcl()
    {
        $this->cache = new AclCache();
        $acl = $this->getAcl(1);

        $acl->updateObjectAce(0, 128);
        $this->getAclProvider()->updateAcl($acl);

        $objectAces = $this->cache->content[$acl->getId()]->getObjectAces();
        $this->assertEquals(128, $objectAces[0]->getMask());
    }

    /**
     * @depends testUpdateAclWritesCacheOfNewAcl
     */
    public function testDeleteAclEvictsFromCache()
    {
        $this->cache = new AclCache();

        $this->getAcl();
        $this->getAclProvider()->deleteAcl($this->getAclObjectIdentity(1));

        $this->assertEmpty($this->cache->content);
    }

    /**
     * @depends testCreateAclWithParent
     * @depends testDeleteAclEvictsFromCache
     */
    public function testDeleteAclEvictsChildrenFromCache()
    {
        $this->cache = new AclCache();

        $parentAcl = $this->getAcl(1);
        $childAcl = $this->getAcl(2);
        $grandChildAcl = $this->getAcl(3);
        $grandChildAcl->setParentAcl($childAcl);
        $childAcl->setParentAcl($parentAcl);

        $this->getAclProvider()->updateAcl($grandChildAcl);
        $this->getAclProvider()->updateAcl($childAcl);

        $this->assertCount(3, $this->cache->content);

        $this->getAclProvider()->deleteAcl($this->getAclObjectIdentity(1));
        $this->assertEmpty($this->cache->content);
    }

    protected function getAcl($identifier = 1)
    {
        $acl = $this->getAclProvider()->createAcl($this->getAclObjectIdentity($identifier));
        $acl->insertObjectAce($this->getRoleSecurityIdentity(), 64);
        $this->getAclProvider()->updateAcl($acl);

        return $acl;
    }
}
