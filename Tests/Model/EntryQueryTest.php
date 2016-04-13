<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Tests\Model\Acl;

use Propel\PropelBundle\Model\Acl\Entry;
use Propel\PropelBundle\Model\Acl\EntryQuery;
use Propel\PropelBundle\Model\Acl\SecurityIdentity;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Propel\PropelBundle\Tests\AclTestCase;

/**
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class EntryQueryTest extends AclTestCase
{
    public function setUp()
    {
        parent::setUp();

        $obj = $this->createModelObjectIdentity(1);
        $entry = $this->createEntry();
        $entry
            ->setSecurityIdentity(SecurityIdentity::fromAclIdentity($this->getRoleSecurityIdentity('ROLE_USER')))
            ->setAclClass($obj->getAclClass())
            ->setMask(64)
        ;
        $obj->addEntry($entry)->save($this->con);
    }

    public function testFindByAclIdentityInvalidSecurityIdentity()
    {
        $this->setExpectedException('InvalidArgumentException');
        EntryQuery::create()->findByAclIdentity($this->getAclObjectIdentity(), array('foo'), $this->con);
    }

    public function testFindByAclIdentityInvalidSecurityIdentityObject()
    {
        $this->setExpectedException('InvalidArgumentException');
        EntryQuery::create()->findByAclIdentity($this->getAclObjectIdentity(), array(new \stdClass()), $this->con);
    }

    public function testFindByAclIdentityNotExists()
    {
        $this->assertCount(0, EntryQuery::create()->findByAclIdentity($this->getAclObjectIdentity(2), array(), $this->con));
    }

    public function testFindByAclIdentitySecurityIdentityNotFound()
    {
        $this->assertCount(0, EntryQuery::create()->findByAclIdentity($this->getAclObjectIdentity(1), array($this->getRoleSecurityIdentity('ROLE_ADMIN')), $this->con));
    }

    public function testFindByAclIdentity()
    {
        // Another Entry, should not be found (different ObjectIdentity).
        $obj = $this->createModelObjectIdentity(2);
        $entry = $this->createEntry();
        $entry
            ->setSecurityIdentity(SecurityIdentity::fromAclIdentity($this->getRoleSecurityIdentity()))
            ->setAclClass($obj->getAclClass())
            ->setMask(64)
        ;
        $obj->addEntry($entry)->save($this->con);

        $entries = EntryQuery::create()->findByAclIdentity($this->getAclObjectIdentity(1), array(), $this->con);
        $this->assertCount(1, $entries);
        $this->assertEquals(1, $entries[0]->getObjectIdentityId());

        // A class based entry for the wrong ObjectIdentity.
        $classEntry = $this->createEntry();
        $classEntry
            ->setObjectIdentityId(2)
            ->setSecurityIdentity(SecurityIdentity::fromAclIdentity($this->getRoleSecurityIdentity()))
            ->setAclClass($obj->getAclClass())
            ->setMask(64)
            ->save($this->con)
        ;

        // A class based entry for the correct ObjectIdentity.
        $classEntry = $this->createEntry();
        $classEntry
            ->setObjectIdentityId(null)
            ->setSecurityIdentity(SecurityIdentity::fromAclIdentity($this->getRoleSecurityIdentity()))
            ->setAclClass($this->getAclClass())
            ->setMask(64)
            ->save($this->con)
        ;

        $this->assertEquals(4, EntryQuery::create()->count($this->con));

        $entries = EntryQuery::create()->findByAclIdentity($this->getAclObjectIdentity(1), array(), $this->con);
        $this->assertCount(2, $entries);
        $this->assertEquals($obj->getClassId(), $entries[0]->getClassId());
        $this->assertEquals($obj->getClassId(), $entries[1]->getClassId());
    }

    public function testFindByAclIdentityFilterSecurityIdentity()
    {
        // Another Entry, should not be found (different SecurityIdentity).
        $entry = $this->createEntry();
        $entry
            ->setObjectIdentityId(1)
            ->setSecurityIdentity(SecurityIdentity::fromAclIdentity($this->getRoleSecurityIdentity('ROLE_ADMIN')))
            ->setAclClass($this->getAclClass())
            ->setMask(64)
            ->save($this->con)
        ;

        $this->assertEquals(2, EntryQuery::create()->count($this->con));

        $entries = EntryQuery::create()->findByAclIdentity($this->getAclObjectIdentity(1), array($this->getRoleSecurityIdentity('ROLE_USER')), $this->con);
        $this->assertCount(1, $entries);
        $this->assertEquals(SecurityIdentity::fromAclIdentity($this->getRoleSecurityIdentity('ROLE_USER'))->getId(), $entries[0]->getSecurityIdentityId());
    }

    public function testFindByAclIdentityOnlyClassEntries()
    {
        $this->assertEquals(1, EntryQuery::create()->count($this->con));
        EntryQuery::create()->findOne($this->con)
            ->setObjectIdentity(null)
            ->save($this->con);

        $entries = EntryQuery::create()->findByAclIdentity($this->getAclObjectIdentity(1), array(), $this->con);
        $this->assertCount(1, $entries);
    }
}
