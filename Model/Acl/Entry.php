<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Model\Acl;

use Propel\PropelBundle\Model\Acl\om\BaseEntry;

use Propel\PropelBundle\Security\Acl\Domain\Entry as AclEntry;
use Propel\PropelBundle\Security\Acl\Domain\FieldEntry as AclFieldEntry;

use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Model\AuditableEntryInterface;
use Symfony\Component\Security\Acl\Model\FieldEntryInterface;

class Entry extends BaseEntry
{
    /**
     * Transform a given ACL entry into a Entry model.
     *
     * The entry will not be persisted!
     *
     * @param EntryInterface $aclEntry
     *
     * @return Entry
     */
    public static function fromAclEntry(EntryInterface $aclEntry)
    {
        $entry = new self();

        // Already persisted before?
        if ($aclEntry->getId()) {
            $entry->setId($aclEntry->getId());
        }

        $entry
            ->setMask($aclEntry->getMask())
            ->setGranting($aclEntry->isGranting())
            ->setGrantingStrategy($aclEntry->getStrategy())
            ->setSecurityIdentity(SecurityIdentity::fromAclIdentity($aclEntry->getSecurityIdentity()))
        ;

        if ($aclEntry instanceof FieldEntryInterface) {
            $entry->setFieldName($aclEntry->getField());
        }

        if ($aclEntry instanceof AuditableEntryInterface) {
            $entry
                ->setAuditFailure($aclEntry->isAuditFailure())
                ->setAuditSuccess($aclEntry->isAuditSuccess())
            ;
        }

        return $entry;
    }

    /**
     * Transform a given model entry into an ACL related Entry (ACE).
     *
     * @param Entry $modelEntry
     * @param AclInterface $acl
     *
     * @return EntryInterface
     */
    public static function toAclEntry(Entry $modelEntry, AclInterface $acl)
    {
        if (null === $modelEntry->getFieldName()) {
            return new AclEntry($modelEntry, $acl);
        } else {
            return new AclFieldEntry($modelEntry, $acl);
        }
    }
}
