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

    public function testUpdateAclInvalidAcl()
    {
        $acl = $this->getMock('Symfony\Component\Security\Acl\Model\MutableAclInterface');

        $this->setExpectedException('InvalidArgumentException');
        $this->getAclProvider()->updateAcl($acl);
    }
}