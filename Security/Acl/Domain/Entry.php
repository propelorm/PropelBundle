<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Security\Acl\Domain;

use Propel\PropelBundle\Model\Acl\Entry as ModelEntry;
use Propel\PropelBundle\Model\Acl\SecurityIdentity;

use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\AuditableEntryInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

/**
 * An ACE implementation retrieving data from a given Propel\PropelBundle\Model\Acl\Entry.
 *
 * The entry is only used to grab a "snapshot" of its data as an EntryInterface is immutable!
 *
 * @see \Symfony\Component\Security\Acl\Model\EntryInterface
 *
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class Entry implements AuditableEntryInterface
{
    protected $acl;

    protected $id;
    protected $securityIdentity;
    protected $mask;
    protected $isGranting;
    protected $strategy;
    protected $auditSuccess;
    protected $auditFailure;

    /**
     * Constructor.
     *
     * @param \Propel\PropelBundle\Model\Acl\Entry               $entry
     * @param \Symfony\Component\Security\Acl\Model\AclInterface $acl
     */
    public function __construct(ModelEntry $entry, AclInterface $acl)
    {
        $this->acl = $acl;
        $this->securityIdentity = SecurityIdentity::toAclIdentity($entry->getSecurityIdentity());

        /*
         * A new ACE (from a MutableAcl) does not have an ID,
         * but will be persisted by the MutableAclProvider afterwards, if issued.
         */
        if ($entry->getId()) {
            $this->id = $entry->getId();
        }

        $this->mask = $entry->getMask();
        $this->isGranting = $entry->getGranting();
        $this->strategy = $entry->getGrantingStrategy();
        $this->auditFailure = $entry->getAuditFailure();
        $this->auditSuccess = $entry->getAuditSuccess();
    }

    /**
     * String representation of object
     *
     * @link http://php.net/manual/en/serializable.serialize.php
     *
     * @return string the string representation of the object or &null;
     */
    public function serialize()
    {
        return serialize(array(
            $this->acl,
            $this->securityIdentity,
            $this->id,
            $this->mask,
            $this->isGranting,
            $this->strategy,
            $this->auditFailure,
            $this->auditSuccess,
        ));
    }

    /**
     * Constructs the object
     *
     * @link http://php.net/manual/en/serializable.unserialize.php
     *
     * @param string $serialized
     *
     * @return mixed the original value unserialized.
     */
    public function unserialize($serialized)
    {
        list(
            $this->acl,
            $this->securityIdentity,
            $this->id,
            $this->mask,
            $this->isGranting,
            $this->strategy,
            $this->auditFailure,
            $this->auditSuccess,
        ) = unserialize($serialized);

        return $this;
    }

    /**
     * The ACL this ACE is associated with.
     *
     * @return \Symfony\Component\Security\Acl\Model\AclInterface
     */
    public function getAcl()
    {
        return $this->acl;
    }

    /**
     * The security identity associated with this ACE
     *
     * @return \Symfony\Component\Security\Acl\Model\SecurityIdentityInterface
     */
    public function getSecurityIdentity()
    {
        return $this->securityIdentity;
    }

    /**
     * The primary key of this ACE
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * The permission mask of this ACE
     *
     * @return integer
     */
    public function getMask()
    {
        return $this->mask;
    }

    /**
     * The strategy for comparing masks
     *
     * @return string
     */
    public function getStrategy()
    {
        return $this->strategy;
    }

    /**
     * Returns whether this ACE is granting, or denying
     *
     * @return bool
     */
    public function isGranting()
    {
        return $this->isGranting;
    }

    /**
     * Whether auditing for successful grants is turned on
     *
     * @return bool
     */
    public function isAuditFailure()
    {
        return $this->auditFailure;
    }

    /**
     * Whether auditing for successful denies is turned on
     *
     * @return bool
     */
    public function isAuditSuccess()
    {
        return $this->auditSuccess;
    }
}
