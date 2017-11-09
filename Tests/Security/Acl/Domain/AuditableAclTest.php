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

use Propel\PropelBundle\Security\Acl\Domain\AuditableAcl;

use Symfony\Component\Security\Acl\Domain\PermissionGrantingStrategy;

use Propel\PropelBundle\Tests\AclTestCase;

/**
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class AuditableAclTest extends AclTestCase
{
    public function testUpdateAuditingInvalidIndex()
    {
        $collection = new \PropelObjectCollection();
        $collection->setModel('Propel\PropelBundle\Model\Acl\Entry');

        $acl = new AuditableAcl($collection, $this->getAclObjectIdentity(), new PermissionGrantingStrategy());

        $this->setExpectedException('OutOfBoundsException');
        $acl->updateObjectAuditing(0, false, false);
    }

    public function testUpdateAuditingInvalidField()
    {
        $collection = new \PropelObjectCollection();
        $collection->setModel('Propel\PropelBundle\Model\Acl\Entry');

        $obj = $this->createModelObjectIdentity(1);
        $entry = $this->createEntry();
        $entry
            ->setObjectIdentity($obj)
            ->setFieldName('name')
            ->setSecurityIdentity(SecurityIdentity::fromAclIdentity($this->getRoleSecurityIdentity()))
            ->setAclClass($this->getAclClass())
        ;
        $collection->append($entry);
        $acl = new AuditableAcl($collection, $this->getAclObjectIdentity(), new PermissionGrantingStrategy());

        $this->setExpectedException('InvalidArgumentException');
        $acl->updateObjectFieldAuditing(0, 'foo', false, false);
    }

    public function testUpdateAuditingInvalidFlag()
    {
        $collection = new \PropelObjectCollection();
        $collection->setModel('Propel\PropelBundle\Model\Acl\Entry');

        $obj = $this->createModelObjectIdentity(1);
        $entry = $this->createEntry();
        $entry
            ->setObjectIdentity($obj)
            ->setSecurityIdentity(SecurityIdentity::fromAclIdentity($this->getRoleSecurityIdentity()))
            ->setAclClass($this->getAclClass())
        ;
        $collection->append($entry);
        $acl = new AuditableAcl($collection, $this->getAclObjectIdentity(), new PermissionGrantingStrategy());

        $this->setExpectedException('InvalidArgumentException');
        $acl->updateObjectAuditing(0, 'foo', 'bar');
    }

    public function testUpdateObjectAuditing()
    {
        $collection = new \PropelObjectCollection();
        $collection->setModel('Propel\PropelBundle\Model\Acl\Entry');

        $obj = $this->createModelObjectIdentity(1);
        $entry = $this->createEntry();
        $entry
            ->setObjectIdentity($obj)
            ->setSecurityIdentity(SecurityIdentity::fromAclIdentity($this->getRoleSecurityIdentity()))
            ->setAclClass($this->getAclClass())
        ;
        $collection->append($entry);
        $acl = new AuditableAcl($collection, $this->getAclObjectIdentity(), new PermissionGrantingStrategy());

        $aces = $acl->getObjectAces();
        $this->assertCount(1, $aces);

        $acl->updateObjectAuditing(0, true, true);
        $aces = $acl->getObjectAces();
        $this->assertTrue($aces[0]->isAuditSuccess());
        $this->assertTrue($aces[0]->isAuditFailure());

        $acl->updateObjectAuditing(0, false, true);
        $aces = $acl->getObjectAces();
        $this->assertFalse($aces[0]->isAuditSuccess());
        $this->assertTrue($aces[0]->isAuditFailure());

        $acl->updateObjectAuditing(0, true, false);
        $aces = $acl->getObjectAces();
        $this->assertTrue($aces[0]->isAuditSuccess());
        $this->assertFalse($aces[0]->isAuditFailure());

        $acl->updateObjectAuditing(0, false, false);
        $aces = $acl->getObjectAces();
        $this->assertFalse($aces[0]->isAuditSuccess());
        $this->assertFalse($aces[0]->isAuditFailure());
    }

    /**
     * @depends testUpdateObjectAuditing
     */
    public function testUpdateObjectFieldAuditing()
    {
        $collection = new \PropelObjectCollection();
        $collection->setModel('Propel\PropelBundle\Model\Acl\Entry');

        $obj = $this->createModelObjectIdentity(1);
        $entry = $this->createEntry();
        $entry
            ->setFieldName('name')
            ->setObjectIdentity($obj)
            ->setSecurityIdentity(SecurityIdentity::fromAclIdentity($this->getRoleSecurityIdentity()))
            ->setAclClass($this->getAclClass())
        ;
        $collection->append($entry);

        $acl = new AuditableAcl($collection, $this->getAclObjectIdentity(), new PermissionGrantingStrategy());

        $aces = $acl->getObjectFieldAces('name');
        $this->assertCount(1, $aces);

        $acl->updateObjectFieldAuditing(0, 'name', true, true);
        $aces = $acl->getObjectFieldAces('name');
        $this->assertTrue($aces[0]->isAuditSuccess());
        $this->assertTrue($aces[0]->isAuditFailure());

        $acl->updateObjectFieldAuditing(0, 'name', false, false);
        $aces = $acl->getObjectFieldAces('name');
        $this->assertFalse($aces[0]->isAuditSuccess());
        $this->assertFalse($aces[0]->isAuditFailure());
    }

    /**
     * @depends testUpdateObjectAuditing
     */
    public function testUpdateClassAuditing()
    {
        $collection = new \PropelObjectCollection();
        $collection->setModel('Propel\PropelBundle\Model\Acl\Entry');

        $entry = $this->createEntry();
        $entry
            ->setSecurityIdentity(SecurityIdentity::fromAclIdentity($this->getRoleSecurityIdentity()))
            ->setAclClass($this->getAclClass())
        ;
        $collection->append($entry);

        $acl = new AuditableAcl($collection, $this->getAclObjectIdentity(), new PermissionGrantingStrategy());

        $aces = $acl->getClassAces();
        $this->assertCount(1, $aces);

        $acl->updateClassAuditing(0, true, true);
        $aces = $acl->getClassAces('name');
        $this->assertTrue($aces[0]->isAuditSuccess());
        $this->assertTrue($aces[0]->isAuditFailure());

        $acl->updateClassAuditing(0, false, false);
        $aces = $acl->getClassAces();
        $this->assertFalse($aces[0]->isAuditSuccess());
        $this->assertFalse($aces[0]->isAuditFailure());
    }

    /**
     * @depends testUpdateObjectAuditing
     */
    public function testUpdateClassFieldAuditing()
    {
        $collection = new \PropelObjectCollection();
        $collection->setModel('Propel\PropelBundle\Model\Acl\Entry');

        $entry = $this->createEntry();
        $entry
            ->setFieldName('name')
            ->setSecurityIdentity(SecurityIdentity::fromAclIdentity($this->getRoleSecurityIdentity()))
            ->setAclClass($this->getAclClass())
        ;
        $collection->append($entry);

        $acl = new AuditableAcl($collection, $this->getAclObjectIdentity(), new PermissionGrantingStrategy());

        $aces = $acl->getClassFieldAces('name');
        $this->assertCount(1, $aces);

        $acl->updateClassFieldAuditing(0, 'name', true, true);
        $aces = $acl->getClassFieldAces('name');
        $this->assertTrue($aces[0]->isAuditSuccess());
        $this->assertTrue($aces[0]->isAuditFailure());

        $acl->updateClassFieldAuditing(0, 'name', false, false);
        $aces = $acl->getClassFieldAces('name');
        $this->assertFalse($aces[0]->isAuditSuccess());
        $this->assertFalse($aces[0]->isAuditFailure());
    }
}
