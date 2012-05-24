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
use Propel\PropelBundle\Model\Acl\ObjectIdentity;
use Propel\PropelBundle\Model\Acl\ObjectIdentityQuery;

use Symfony\Component\Security\Acl\Domain\PermissionGrantingStrategy;

use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\MutableAclInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

/**
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class MutableAcl extends Acl implements MutableAclInterface
{
    /**
     * The id of the current ACL.
     *
     * It's the id of the ObjectIdentity model.
     *
     * @var int
     */
    protected $id;

    /**
     * A reference to the ObjectIdentity this ACL is mapped to.
     *
     * @var \Propel\PropelBundle\Model\Acl\ObjectIdentity
     */
    protected $modelObjectIdentity;

    /**
     * A connection to be used for all changes on the ACL.
     *
     * @var \PropelPDO
     */
    protected $con;

    /**
     * Constructor.
     *
     * @param \PropelObjectCollection                                                   $entries
     * @param \Symfony\Component\Security\Acl\Model\ObjectIdentityInterface             $objectIdentity
     * @param \Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface $permissionGrantingStrategy
     * @param array                                                                     $loadedSecurityIdentities
     * @param \Symfony\Component\Security\Acl\Model\AclInterface                        $parentAcl
     * @param bool                                                                      $inherited
     * @param \PropelPDO                                                                $con
     */
    public function __construct(\PropelObjectCollection $entries, ObjectIdentityInterface $objectIdentity, PermissionGrantingStrategyInterface $permissionGrantingStrategy, array $loadedSecurityIdentities = array(), AclInterface $parentAcl = null, $inherited = true, \PropelPDO $con = null)
    {
        parent::__construct($entries, $objectIdentity, $permissionGrantingStrategy, $loadedSecurityIdentities, $parentAcl, $inherited);

        $this->modelObjectIdentity = ObjectIdentityQuery::create()
            ->filterByAclObjectIdentity($objectIdentity, $con)
            ->findOneOrCreate($con)
        ;

        if ($this->modelObjectIdentity->isNew()) {
            $this->modelObjectIdentity->save($con);
        }

        $this->id = $this->modelObjectIdentity->getId();

        $this->con = $con;
    }

    /**
     * Returns the primary key of this ACL
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets whether entries are inherited
     *
     * @param bool $boolean
     */
    public function setEntriesInheriting($boolean)
    {
        $this->inherited = $boolean;
    }

    /**
     * Sets the parent ACL
     *
     * @param \Symfony\Component\Security\Acl\Model\AclInterface|null $acl
     */
    public function setParentAcl(AclInterface $acl = null)
    {
        $this->parentAcl = $acl;
    }

    /**
     * Deletes a class-based ACE
     *
     * @param integer $index
     */
    public function deleteClassAce($index)
    {
        $this->deleteIndex($this->classAces, $index);
    }

    /**
     * Deletes a class-field-based ACE
     *
     * @param integer $index
     * @param string  $field
     */
    public function deleteClassFieldAce($index, $field)
    {
        $this
            ->validateField($this->classFieldAces, $field)
            ->deleteIndex($this->classFieldAces[$field], $index)
        ;
    }

    /**
     * Deletes an object-based ACE
     *
     * @param integer $index
     */
    public function deleteObjectAce($index)
    {
        $this->deleteIndex($this->objectAces, $index);
    }

    /**
     * Deletes an object-field-based ACE
     *
     * @param integer $index
     * @param string  $field
     */
    public function deleteObjectFieldAce($index, $field)
    {
        $this
            ->validateField($this->objectFieldAces, $field)
            ->deleteIndex($this->objectFieldAces[$field], $index)
        ;
    }

    /**
     * Inserts a class-based ACE
     *
     * @param \Symfony\Component\Security\Acl\Model\SecurityIdentityInterface $securityIdentity
     * @param integer                                                         $mask
     * @param integer                                                         $index
     * @param bool                                                            $granting
     * @param string                                                          $strategy
     */
    public function insertClassAce(SecurityIdentityInterface $securityIdentity, $mask, $index = 0, $granting = true, $strategy = null)
    {
        $this->insertToList($this->classAces, $index, $this->createAce($mask, $index, $securityIdentity, $strategy, $granting));
    }

    /**
     * Inserts a class-field-based ACE
     *
     * @param string                                                          $field
     * @param \Symfony\Component\Security\Acl\Model\SecurityIdentityInterface $securityIdentity
     * @param integer                                                         $mask
     * @param integer                                                         $index
     * @param boolean                                                         $granting
     * @param string                                                          $strategy
     */
    public function insertClassFieldAce($field, SecurityIdentityInterface $securityIdentity, $mask, $index = 0, $granting = true, $strategy = null)
    {
        if (!isset($this->classFieldAces[$field])) {
            $this->classFieldAces[$field] = array();
        }

        $this->insertToList($this->classFieldAces[$field], $index, $this->createAce($mask, $index, $securityIdentity, $strategy, $granting, $field));
    }

    /**
     * Inserts an object-based ACE
     *
     * @param \Symfony\Component\Security\Acl\Model\SecurityIdentityInterface $securityIdentity
     * @param integer                                                         $mask
     * @param integer                                                         $index
     * @param boolean                                                         $granting
     * @param string                                                          $strategy
     */
    public function insertObjectAce(SecurityIdentityInterface $securityIdentity, $mask, $index = 0, $granting = true, $strategy = null)
    {
        $this->insertToList($this->objectAces, $index, $this->createAce($mask, $index, $securityIdentity, $strategy, $granting));
    }

    /**
     * Inserts an object-field-based ACE
     *
     * @param string                                                          $field
     * @param \Symfony\Component\Security\Acl\Model\SecurityIdentityInterface $securityIdentity
     * @param integer                                                         $mask
     * @param integer                                                         $index
     * @param boolean                                                         $granting
     * @param string                                                          $strategy
     */
    public function insertObjectFieldAce($field, SecurityIdentityInterface $securityIdentity, $mask, $index = 0, $granting = true, $strategy = null)
    {
        if (!isset($this->objectFieldAces[$field])) {
            $this->objectFieldAces[$field] = array();
        }

        $this->insertToList($this->objectFieldAces[$field], $index, $this->createAce($mask, $index, $securityIdentity, $strategy, $granting, $field));
    }

    /**
     * Updates a class-based ACE
     *
     * @param integer $index
     * @param integer $mask
     * @param string  $strategy if null the strategy should not be changed
     */
    public function updateClassAce($index, $mask, $strategy = null)
    {
        $this->updateAce($this->classAces, $index, $mask, $strategy);
    }

    /**
     * Updates a class-field-based ACE
     *
     * @param integer $index
     * @param string  $field
     * @param integer $mask
     * @param string  $strategy if null the strategy should not be changed
     */
    public function updateClassFieldAce($index, $field, $mask, $strategy = null)
    {
        $this
            ->validateField($this->classFieldAces, $field)
            ->updateAce($this->classFieldAces[$field], $index, $mask, $strategy)
        ;
    }

    /**
     * Updates an object-based ACE
     *
     * @param integer $index
     * @param integer $mask
     * @param string  $strategy if null the strategy should not be changed
     */
    public function updateObjectAce($index, $mask, $strategy = null)
    {
        $this->updateAce($this->objectAces, $index, $mask, $strategy);
    }

    /**
     * Updates an object-field-based ACE
     *
     * @param integer $index
     * @param string  $field
     * @param integer $mask
     * @param string  $strategy if null the strategy should not be changed
     */
    public function updateObjectFieldAce($index, $field, $mask, $strategy = null)
    {
        $this->validateField($this->objectFieldAces, $field);
        $this->updateAce($this->objectFieldAces[$field], $index, $mask, $strategy);
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
            $this->id,
            $this->modelObjectIdentity,
            $this->model,
            $this->classAces,
            $this->classFieldAces,
            $this->objectAces,
            $this->objectFieldAces,
            $this->objectIdentity,
            $this->parentAcl,
            $this->permissionGrantingStrategy,
            $this->inherited,
            $this->loadedSecurityIdentities,
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
            $this->id,
            $this->modelObjectIdentity,
            $this->model,
            $this->classAces,
            $this->classFieldAces,
            $this->objectAces,
            $this->objectFieldAces,
            $this->objectIdentity,
            $this->parentAcl,
            $this->permissionGrantingStrategy,
            $this->inherited,
            $this->loadedSecurityIdentities,
        ) = unserialize($serialized);

        return $this;
    }

    /**
     * Insert a given entry into the list on the given index by shifting all others.
     *
     * @param array                                      $list
     * @param int                                        $index
     * @param \Propel\PropelBundle\Model\Acl\Entry\Entry $entry
     *
     * @return \Propel\PropelBundle\Security\Acl\Domain\MutableAcl $this
     */
    protected function insertToList(array &$list, $index, Entry $entry)
    {
        $this->isWithinBounds($list, $index);

        if ($entry instanceof FieldEntry) {
            $this->updateFields($entry->getField());
        }

        $list = array_merge(
            array_slice($list, 0, $index),
            array($entry),
            array_splice($list, $index)
        );

        return $this;
    }

    /**
     * Update a single ACE of this ACL.
     *
     * @param array  $list
     * @param int    $index
     * @param int    $mask
     * @param string $strategy
     * @param string $field
     *
     * @return \Propel\PropelBundle\Security\Acl\Domain\MutableAcl $this
     */
    protected function updateAce(array &$list, $index, $mask, $strategy = null)
    {
        $this->validateIndex($list, $index);

        $entry = ModelEntry::fromAclEntry($list[$index]);

        // Apply updates
        $entry->setMask($mask);
        if (null !== $strategy) {
            $entry->setGrantingStrategy($strategy);
        }

        $list[$index] = ModelEntry::toAclEntry($entry, $this);

        return $this;
    }

    /**
     * Delete the ACE of the given list and index.
     *
     * The list will be re-ordered to have a valid 0..x list.
     *
     * @param array $list
     * @param $index
     *
     * @return \Propel\PropelBundle\Security\Acl\Domain\MutableAcl $this
     */
    protected function deleteIndex(array &$list, $index)
    {
        $this->validateIndex($list, $index);
        unset($list[$index]);
        $this->reorderList($list, $index-1);

        return $this;
    }

    /**
     * Validate the index on the given list of ACEs.
     *
     * @throws \OutOfBoundsException
     *
     * @param array $list
     * @param int   $index
     *
     * @return \Propel\PropelBundle\Security\Acl\Domain\MutableAcl $this
     */
    protected function isWithinBounds(array &$list, $index)
    {
        // No count()-1, the count is one ahead of index, and could create the next valid entry!
        if ($index < 0 or $index > count($list)) {
            throw new \OutOfBoundsException(sprintf('The index must be in the interval [0, %d].', count($list)));
        }

        return $this;
    }

    /**
     * Check the index for existence in the given list.
     *
     * @throws \OutOfBoundsException
     *
     * @param array $list
     * @param $index
     *
     * @return \Propel\PropelBundle\Security\Acl\Domain\MutableAcl $this
     */
    protected function validateIndex(array &$list, $index)
    {
        if (!isset($list[$index])) {
            throw new \OutOfBoundsException(sprintf('The index "%d" does not exist.', $index));
        }

        return $this;
    }

    /**
     * Validate the given field to be present.
     *
     * @throws \InvalidArgumentException
     *
     * @param array  $list
     * @param string $field
     *
     * @return \Propel\PropelBundle\Security\Acl\Domain\MutableAcl $this
     */
    protected function validateField(array &$list, $field)
    {
        if (!isset($list[$field])) {
            throw new \InvalidArgumentException(sprintf('The given field "%s" does not exist.', $field));
        }

        return $this;
    }

    /**
     * Order the given list to have numeric indexes from 0..x
     *
     * @param array $list
     * @param int   $index The right boundary to which the list is valid.
     *
     * @return \Propel\PropelBundle\Security\Acl\Domain\MutableAcl $this
     */
    protected function reorderList(array &$list, $index)
    {
        $list = array_merge(
            array_slice($list, 0, $index+1), // +1 to get length
            array_splice($list, $index+1)    // +1 to get first index to re-order
        );

        return $this;
    }

    /**
     * Create a new ACL Entry.
     *
     * @param int                                                             $mask
     * @param int                                                             $index
     * @param \Symfony\Component\Security\Acl\Model\SecurityIdentityInterface $securityIdentity
     * @param string                                                          $strategy
     * @param bool                                                            $granting
     * @param string                                                          $field
     *
     * @return \Propel\PropelBundle\Security\Acl\Domain\Entry|\Propel\PropelBundle\Security\Acl\Domain\FieldEntry
     */
    protected function createAce($mask, $index, SecurityIdentityInterface $securityIdentity, $strategy = null, $granting = true, $field = null)
    {
        if (!is_int($mask)) {
            throw new \InvalidArgumentException('The given mask is not valid. Please provide an integer.');
        }

        // Compatibility with default implementation
        if (null === $strategy) {
            if (true === $granting) {
                $strategy = PermissionGrantingStrategy::ALL;
            } else {
                $strategy = PermissionGrantingStrategy::ANY;
            }
        }

        $model = new ModelEntry();
        $model
            ->setAceOrder($index)
            ->setMask($mask)
            ->setGrantingStrategy($strategy)
            ->setGranting($granting)
            ->setSecurityIdentity(SecurityIdentity::fromAclIdentity($securityIdentity))
        ;

        if (null !== $field) {
            $model->setFieldName($field);

            return new FieldEntry($model, $this);
        }

        return new Entry($model, $this);
    }
}
