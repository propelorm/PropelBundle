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
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

/**
 * An ACE implementation retrieving data from a given Propel\PropelBundle\Model\Acl\Entry.
 *
 * The entry is only used to grab a "snapshot" of its data as an EntryInterface is immutable!
 *
 * @see Symfony\Component\Security\Acl\Model\EntryInterface
 *
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class FieldEntry extends Entry implements FieldEntryInterface
{
    protected $field;

    /**
     * Constructor.
     *
     * @param ModelEntry $entry
     * @param AclInterface $acl
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
}