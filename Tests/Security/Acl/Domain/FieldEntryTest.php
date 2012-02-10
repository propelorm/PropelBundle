<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Tests\Security\Acl\Domain;

use Propel\PropelBundle\Model\Acl\Entry as ModelEntry;
use Propel\PropelBundle\Model\Acl\SecurityIdentity;

use Propel\PropelBundle\Security\Acl\Domain\Acl;
use Propel\PropelBundle\Security\Acl\Domain\FieldEntry;

use Symfony\Component\Security\Acl\Domain\PermissionGrantingStrategy;

use Propel\PropelBundle\Tests\AclTestCase;

/**
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class FieldEntryTest extends AclTestCase
{
    public function testConstruct()
    {
        $collection = new \PropelObjectCollection();
        $collection->setModel('Propel\PropelBundle\Model\Acl\Entry');
        $acl = new Acl($collection, $this->getAclObjectIdentity(), new PermissionGrantingStrategy());

        $model = $this->createEntry();
        $model->setFieldName('name');
        $model->setAuditFailure(true);
        $model->setSecurityIdentity(SecurityIdentity::fromAclIdentity($this->getRoleSecurityIdentity()));

        $entry = new FieldEntry($model, $acl);

        $this->assertEquals($model->getMask(), $entry->getMask());
        $this->assertEquals($model->getGranting(), $entry->isGranting());
        $this->assertEquals($model->getGrantingStrategy(), $entry->getStrategy());
        $this->assertEquals($model->getAuditFailure(), $entry->isAuditFailure());
        $this->assertEquals($model->getAuditSuccess(), $entry->isAuditSuccess());
        $this->assertEquals($model->getFieldName(), $entry->getField());
        $this->assertEquals($this->getRoleSecurityIdentity(), $entry->getSecurityIdentity());

        return $entry;
    }

    /**
     * @depends testConstruct
     */
    public function testSerializeUnserialize(FieldEntry $entry)
    {
        $serialized = serialize($entry);
        $unserialized = unserialize($serialized);

        $this->assertNotEmpty($serialized);
        $this->assertNotEmpty($unserialized);
        $this->assertInstanceOf('Propel\PropelBundle\Security\Acl\Domain\FieldEntry', $unserialized);

        $this->assertEquals($entry->getMask(), $unserialized->getMask());
        $this->assertEquals($entry->isGranting(), $unserialized->isGranting());
        $this->assertEquals($entry->getStrategy(), $unserialized->getStrategy());
        $this->assertEquals($entry->isAuditFailure(), $unserialized->isAuditFailure());
        $this->assertEquals($entry->isAuditSuccess(), $unserialized->isAuditSuccess());
        $this->assertEquals($entry->getSecurityIdentity(), $unserialized->getSecurityIdentity());
        $this->assertEquals($entry->getField(), $unserialized->getField());

        $this->assertEquals($serialized, serialize($unserialized));
    }
}