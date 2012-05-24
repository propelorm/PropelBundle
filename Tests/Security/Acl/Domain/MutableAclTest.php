<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Tests\Security\Acl\Domain;

use Propel\PropelBundle\Model\Acl\Entry;
use Propel\PropelBundle\Model\Acl\SecurityIdentity;

use Propel\PropelBundle\Security\Acl\Domain\MutableAcl;

use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Domain\PermissionGrantingStrategy;

use Propel\PropelBundle\Tests\AclTestCase;

/**
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class MutableAclTest extends AclTestCase
{
    public function testConstructorInvalidCollection()
    {
        $collection = new \PropelObjectCollection();
        $collection->setModel('Propel\PropelBundle\Model\Acl\AclClass');

        $this->setExpectedException('Symfony\Component\Security\Acl\Exception\Exception');
        new MutableAcl($collection, $this->getAclObjectIdentity(), new PermissionGrantingStrategy(), array(), null, false, $this->con);
    }

    public function testConstructorEmptyCollection()
    {
        $acl = $this->createEmptyAcl(1, array(), null, false);

        $this->assertEquals(1, $acl->getId());
        $this->assertEmpty($acl->getClassAces());
        $this->assertEmpty($acl->getObjectAces());
        $this->assertEmpty($acl->getFields());
        $this->assertNull($acl->getParentAcl());
        $this->assertFalse($acl->isEntriesInheriting());
    }

    /**
     * @depends testConstructorEmptyCollection
     */
    public function testSetUnsetParentAcl()
    {
        $parentAcl = $this->createEmptyAcl(1);
        $acl = $this->createEmptyAcl(2);

        $acl->setParentAcl($parentAcl);
        $acl->setEntriesInheriting(true);

        $this->assertSame($parentAcl, $acl->getParentAcl());
        $this->assertTrue($acl->isEntriesInheriting());
        $this->assertEquals(1, $acl->getParentAcl()->getId());

        $acl->setParentAcl(null);
        $this->assertNull($acl->getParentAcl());
    }

    public function testInsertAceInvalidMask()
    {
        $acl = $this->createEmptyAcl();
        $this->setExpectedException('InvalidArgumentException', 'The given mask is not valid. Please provide an integer.');
        $acl->insertClassAce($this->getRoleSecurityIdentity(), 'foo');
    }

    public function testInsertAceOutofBounds()
    {
        $acl = $this->createEmptyAcl();
        $this->setExpectedException('OutOfBoundsException', 'The index must be in the interval [0, 0].');
        $acl->insertClassAce($this->getRoleSecurityIdentity(), 64, 1);
    }

    public function insertAceProvider()
    {
        return array(
            array('ClassAce'),
            array('ClassFieldAce', 'name'),
            array('ObjectAce'),
            array('ObjectFieldAce', 'name'),
        );
    }

    /**
     * @dataProvider insertAceProvider
     */
    public function testInsertFirstAce($type, $field = null)
    {
        $acl = $this->createEmptyAcl();

        if (null !== $field) {
            $acl->{'insert'.$type}($field, $this->getRoleSecurityIdentity(), 64);
            $aces = $acl->{'get'.$type.'s'}($field);
        } else {
            $acl->{'insert'.$type}($this->getRoleSecurityIdentity(), 64);
            $aces = $acl->{'get'.$type.'s'}();
        }

        $this->assertNotEmpty($aces);
        $this->assertCount(1, $aces);
        $this->assertEquals($this->getRoleSecurityIdentity(), $aces[0]->getSecurityIdentity());
        $this->assertEquals(64, $aces[0]->getMask());
        $this->assertTrue($aces[0]->isGranting());
        $this->assertNull($aces[0]->getId());
        $this->assertEquals('all', $aces[0]->getStrategy());

        if (null !== $field) {
            $this->assertEquals($field, $aces[0]->getField());
        }
    }

    public function testUpdateAceInvalidIndex()
    {
        $acl = $this->createEmptyAcl();
        $this->setExpectedException('OutOfBoundsException');
        $acl->updateClassAce(0, 64);
    }

    /**
     * @depends testInsertFirstAce
     */
    public function testUpdateFieldAceInvalidField()
    {
        $acl = $this->createEmptyAcl();
        $acl->insertClassAce($this->getRoleSecurityIdentity(), 64);

        $this->setExpectedException('InvalidArgumentException', 'The given field "name" does not exist.');
        $acl->updateClassFieldAce(0, 'name', 128);
    }

    /**
     * @depends testInsertFirstAce
     */
    public function testInsertUpdateDelete()
    {
        $secIdentity = $this->getRoleSecurityIdentity();

        $acl = $this->createEmptyAcl();

        // insert

        $acl->insertClassAce($secIdentity, 64);
        $acl->insertClassFieldAce('name', $secIdentity, 32);
        $acl->insertObjectAce($secIdentity, 128);
        $acl->insertObjectFieldAce('name', $secIdentity, 16, 0, false);

        $classAces = $acl->getClassAces();
        $classFieldAces = $acl->getClassFieldAces('name');
        $objectAces = $acl->getObjectAces();
        $objectFieldAces = $acl->getObjectFieldAces('name');

        $this->assertCount(1, $classAces);
        $this->assertCount(1, $classFieldAces);
        $this->assertCount(1, $objectAces);
        $this->assertCount(1, $objectFieldAces);
        $this->assertEquals(array('name'), $acl->getFields());

        $this->assertEquals(64, $classAces[0]->getMask());
        $this->assertEquals(32, $classFieldAces[0]->getMask());
        $this->assertEquals(128, $objectAces[0]->getMask());
        $this->assertEquals(16, $objectFieldAces[0]->getMask());

        $this->assertEquals('all', $classAces[0]->getStrategy());
        $this->assertEquals('all', $classFieldAces[0]->getStrategy());
        $this->assertEquals('all', $objectAces[0]->getStrategy());
        $this->assertEquals('any', $objectFieldAces[0]->getStrategy());

        $this->assertFalse($objectFieldAces[0]->isGranting());

        // update

        $acl->updateClassAce(0, 256);
        $acl->updateClassFieldAce(0, 'name', 128, 'any');
        $acl->updateObjectAce(0, 64, 'equal');
        $acl->updateObjectFieldAce(0, 'name', 32, 'all');

        $this->assertCount(1, $classAces);
        $this->assertCount(1, $classFieldAces);
        $this->assertCount(1, $objectAces);
        $this->assertCount(1, $objectFieldAces);

        $classAces = $acl->getClassAces();
        $classFieldAces = $acl->getClassFieldAces('name');
        $objectAces = $acl->getObjectAces();
        $objectFieldAces = $acl->getObjectFieldAces('name');

        $this->assertEquals(256, $classAces[0]->getMask());
        $this->assertEquals(128, $classFieldAces[0]->getMask());
        $this->assertEquals(64, $objectAces[0]->getMask());
        $this->assertEquals(32, $objectFieldAces[0]->getMask());

        $this->assertEquals('all', $classAces[0]->getStrategy());
        $this->assertEquals('any', $classFieldAces[0]->getStrategy());
        $this->assertEquals('equal', $objectAces[0]->getStrategy());
        $this->assertEquals('all', $objectFieldAces[0]->getStrategy());

        // delete

        $acl->deleteClassAce(0);
        $acl->deleteClassFieldAce(0, 'name');
        $acl->deleteObjectAce(0);
        $acl->deleteObjectFieldAce(0, 'name');

        $classAces = $acl->getClassAces();
        $classFieldAces = $acl->getClassFieldAces('name');
        $objectAces = $acl->getObjectAces();
        $objectFieldAces = $acl->getObjectFieldAces('name');

        $this->assertCount(0, $classAces);
        $this->assertCount(0, $classFieldAces);
        $this->assertCount(0, $objectAces);
        $this->assertCount(0, $objectFieldAces);
    }

    /**
     * @depends testInsertUpdateDelete
     */
    public function testUpdatePersistedAceKeepsId()
    {
        $collection = new \PropelObjectCollection();
        $collection->setModel('Propel\PropelBundle\Model\Acl\Entry');

        $entry = $this->createEntry();
        $entry
            ->setId(42)
            ->setSecurityIdentity(SecurityIdentity::fromAclIdentity($this->getRoleSecurityIdentity('ROLE_ADMIN')))
            ->setAclClass($this->getAclClass())
        ;
        $collection->append($entry);

        $acl = new MutableAcl($collection, $this->getAclObjectIdentity(), new PermissionGrantingStrategy());
        $acl->updateClassAce(0, 128);

        $aces = $acl->getClassAces();
        $this->assertEquals(42, $aces[0]->getId());
        $this->assertEquals(128, $aces[0]->getMask());
    }

    public function testSerializeUnserialize()
    {
        $collection = new \PropelObjectCollection();
        $collection->setModel('Propel\PropelBundle\Model\Acl\Entry');

        $entry = $this->createEntry();
        $entry
            ->setSecurityIdentity(SecurityIdentity::fromAclIdentity($this->getRoleSecurityIdentity('ROLE_ADMIN')))
            ->setAclClass($this->getAclClass())
        ;
        $collection->append($entry);

        $acl = new MutableAcl($collection, $this->getAclObjectIdentity(), new PermissionGrantingStrategy());
        $serialized = serialize($acl);
        $unserialized = unserialize($serialized);

        $this->assertNotEmpty($serialized);
        $this->assertNotEmpty($unserialized);
        $this->assertInstanceOf('Propel\PropelBundle\Security\Acl\Domain\MutableAcl', $unserialized);
        $this->assertEquals($serialized, serialize($unserialized));
    }

    protected function createEmptyAcl($identifier = 1, array $securityIdentities = array(), AclInterface $parentAcl = null, $inherited = null)
    {
        $collection = new \PropelObjectCollection();
        $collection->setModel('Propel\PropelBundle\Model\Acl\Entry');

        return new MutableAcl($collection, $this->getAclObjectIdentity($identifier), new PermissionGrantingStrategy(), $securityIdentities, $parentAcl, $inherited, $this->con);
    }
}
