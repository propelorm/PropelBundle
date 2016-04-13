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

use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\FieldEntryInterface;

/**
 * An ACE implementation retrieving data from a given \Propel\PropelBundle\Model\Acl\Entry.
 *
 * The entry is only used to grab a "snapshot" of its data as an \Symfony\Component\Security\Acl\Model\EntryInterface is immutable!
 *
 * @see \Symfony\Component\Security\Acl\Model\EntryInterface
 *
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class FieldEntry extends Entry implements FieldEntryInterface
{
    protected $field;

    /**
     * Constructor.
     *
     * @param \Propel\PropelBundle\Model\Acl\Entry               $entry
     * @param \Symfony\Component\Security\Acl\Model\AclInterface $acl
     */
    public function __construct(ModelEntry $entry, AclInterface $acl)
    {
        $this->field = $entry->getFieldName();

        parent::__construct($entry, $acl);
    }

    /**
     * Returns the field used for this entry.
     *
     * @return string
     */
    public function getField()
    {
        return $this->field;
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
            $this->field,
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
            $this->field,
        ) = unserialize($serialized);

        return $this;
    }
}
