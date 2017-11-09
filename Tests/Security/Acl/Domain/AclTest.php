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

use Propel\PropelBundle\Security\Acl\Domain\Acl;

use Symfony\Component\Security\Acl\Domain\PermissionGrantingStrategy;

use Propel\PropelBundle\Tests\AclTestCase;

/**
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class AclTest extends AclTestCase
{
    public function testConstructorInvalidCollection()
    {
        $collection = new \PropelObjectCollection();
        $collection->setModel('Propel\PropelBundle\Model\Acl\AclClass');

        $this->setExpectedException('Symfony\Component\Security\Acl\Exception\Exception');
        new Acl($collection, $this->getAclObjectIdentity(), new PermissionGrantingStrategy());
    }

    public function testConstructorEmptyCollection()
    {
        $collection = new \PropelObjectCollection();
        $collection->setModel('Propel\PropelBundle\Model\Acl\Entry');

        $aclObj = $this->getAclObjectIdentity();
        $acl = new Acl($collection, $aclObj, new PermissionGrantingStrategy());

        $this->assertEmpty($acl->getClassAces());
        $this->assertEmpty($acl->getObjectAces());
        $this->assertEmpty($acl->getFields());
        $this->assertNull($acl->getParentAcl());
        $this->assertSame($aclObj, $acl->getObjectIdentity());
        $this->assertTrue($acl->isEntriesInheriting());
    }

    /**
     * @depends testConstructorEmptyCollection
     */
    public function testConstructorWithAces()
    {
        $collection = new \PropelObjectCollection();
        $collection->setModel('Propel\PropelBundle\Model\Acl\Entry');

        $obj = $this->createModelObjectIdentity(1);

        // object based ACE
        $entry = $this->createEntry();
        $entry
            ->setObjectIdentity($obj)
            ->setSecurityIdentity(SecurityIdentity::fromAclIdentity($this->getRoleSecurityIdentity()))
            ->setAclClass($this->getAclClass())
        ;
        $collection->append($entry);

        // object field based ACE
        $entry = $this->createEntry();
        $entry
            ->setObjectIdentity($obj)
            ->setFieldName('name')
            ->setSecurityIdentity(SecurityIdentity::fromAclIdentity($this->getRoleSecurityIdentity()))
            ->setAclClass($this->getAclClass())
        ;
        $collection->append($entry);

        // class based ACE
        $entry = $this->createEntry();
        $entry
            ->setSecurityIdentity(SecurityIdentity::fromAclIdentity($this->getRoleSecurityIdentity()))
            ->setAclClass($this->getAclClass())
        ;
        $collection->append($entry);

        // class field based ACE
        $entry = $this->createEntry();
        $entry
            ->setFieldName('name')
            ->setSecurityIdentity(SecurityIdentity::fromAclIdentity($this->getRoleSecurityIdentity()))
            ->setAclClass($this->getAclClass())
        ;
        $collection->append($entry);

        $acl = new Acl($collection, $this->getAclObjectIdentity(), new PermissionGrantingStrategy());
        $this->assertNotEmpty($acl->getClassAces());
        $this->assertNotEmpty($acl->getObjectAces());
        $this->assertEquals(array('name'), $acl->getFields());
        $this->assertNotEmpty($acl->getClassFieldAces('name'));
        $this->assertNotEmpty($acl->getObjectFieldAces('name'));

        $classAces = $acl->getClassAces();
        $objectAces = $acl->getObjectAces();
        $classFieldAces = $acl->getClassFieldAces('name');
        $objectFieldAces = $acl->getObjectFieldAces('name');

        $this->assertInstanceOf('Propel\PropelBundle\Security\Acl\Domain\Entry', $classAces[0]);
        $this->assertInstanceOf('Propel\PropelBundle\Security\Acl\Domain\Entry', $objectAces[0]);
        $this->assertInstanceOf('Propel\PropelBundle\Security\Acl\Domain\FieldEntry', $classFieldAces[0]);
        $this->assertInstanceOf('Propel\PropelBundle\Security\Acl\Domain\FieldEntry', $objectFieldAces[0]);

        $this->assertSame($acl, $classAces[0]->getAcl());
        $this->assertSame($acl, $objectAces[0]->getAcl());
        $this->assertSame($acl, $classFieldAces[0]->getAcl());
        $this->assertSame($acl, $objectFieldAces[0]->getAcl());

        $this->assertEquals('name', $classFieldAces[0]->getField());
        $this->assertEquals('name', $objectFieldAces[0]->getField());
    }

    public function testIsSidLoadedNoneLoaded()
    {
        $collection = new \PropelObjectCollection();
        $collection->setModel('Propel\PropelBundle\Model\Acl\Entry');
        $acl = new Acl($collection, $this->getAclObjectIdentity(), new PermissionGrantingStrategy());

        $this->assertFalse($acl->isSidLoaded($this->getRoleSecurityIdentity()));
    }

    public function testIsSidLoadedInvalid()
    {
        $collection = new \PropelObjectCollection();
        $collection->setModel('Propel\PropelBundle\Model\Acl\Entry');

        $aclObj = $this->getAclObjectIdentity();
        $acl = new Acl($collection, $aclObj, new PermissionGrantingStrategy());

        $this->setExpectedException('InvalidArgumentException');
        $acl->isSidLoaded('foo');
    }

    public function testIsGrantedNoAces()
    {
        $collection = new \PropelObjectCollection();
        $collection->setModel('Propel\PropelBundle\Model\Acl\Entry');

        $acl = new Acl($collection, $this->getAclObjectIdentity(), new PermissionGrantingStrategy());

        $this->setExpectedException('Symfony\Component\Security\Acl\Exception\NoAceFoundException');
        $acl->isGranted(array(64), array($this->getRoleSecurityIdentity()));
    }

    public function testIsGrantedNoMatchingSecurityIdentity()
    {
        $collection = new \PropelObjectCollection();
        $collection->setModel('Propel\PropelBundle\Model\Acl\Entry');

        $entry = $this->createEntry();
        $entry
            ->setSecurityIdentity(SecurityIdentity::fromAclIdentity($this->getRoleSecurityIdentity('ROLE_ADMIN')))
            ->setAclClass($this->getAclClass())
        ;
        $collection->append($entry);

        $acl = new Acl($collection, $this->getAclObjectIdentity(), new PermissionGrantingStrategy());

        $this->setExpectedException('Symfony\Component\Security\Acl\Exception\NoAceFoundException');
        $acl->isGranted(array(64), array($this->getRoleSecurityIdentity('ROLE_USER')));
    }

    public function testIsFieldGrantedNoAces()
    {
        $collection = new \PropelObjectCollection();
        $collection->setModel('Propel\PropelBundle\Model\Acl\Entry');

        $acl = new Acl($collection, $this->getAclObjectIdentity(), new PermissionGrantingStrategy());

        $this->setExpectedException('Symfony\Component\Security\Acl\Exception\NoAceFoundException');
        $acl->isFieldGranted('name', array(64), array($this->getRoleSecurityIdentity()));
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

        $acl = new Acl($collection, $this->getAclObjectIdentity(), new PermissionGrantingStrategy());
        $serialized = serialize($acl);
        $unserialized = unserialize($serialized);

        $this->assertNotEmpty($serialized);
        $this->assertNotEmpty($unserialized);
        $this->assertInstanceOf('Propel\PropelBundle\Security\Acl\Domain\Acl', $unserialized);
        $this->assertEquals($serialized, serialize($unserialized));
    }
}
