<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Security\Acl\Domain;

use Symfony\Component\Security\Acl\Exception\Exception as AclException;

use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

/**
 * An ACL implementation that is immutable based on data from a PropelObjectCollection of Propel\PropelBundle\Model\Acl\Entry.
 *
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class Acl implements AclInterface
{
    protected $model = 'Propel\PropelBundle\Model\Acl\Entry';

    protected $classAces = array();
    protected $classFieldAces = array();
    protected $objectAces = array();
    protected $objectFieldAces = array();

    protected $objectIdentity;
    protected $parentAcl;
    protected $permissionGrantingStrategy;
    protected $inherited;

    protected $loadedSecurityIdentities = array();

    /**
     * A list of known associated fields on this ACL.
     *
     * @var array
     */
    protected $fields = array();

    /**
     * Constructor.
     *
     * @param \PropelObjectCollection                                                   $entries
     * @param \Symfony\Component\Security\Acl\Model\ObjectIdentityInterface             $objectIdentity
     * @param \Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface $permissionGrantingStrategy
     * @param array                                                                     $loadedSecurityIdentities
     * @param \Symfony\Component\Security\Acl\Model\AclInterface                        $parentAcl
     * @param bool                                                                      $inherited
     */
    public function __construct(\PropelObjectCollection $entries, ObjectIdentityInterface $objectIdentity, PermissionGrantingStrategyInterface $permissionGrantingStrategy, array $loadedSecurityIdentities = array(), AclInterface $parentAcl = null, $inherited = true)
    {
        if ($entries->getModel() !== $this->model) {
            throw new AclException(sprintf('The given collection does not contain models of class "%s" but of class "%s".', $this->model, $entries->getModel()));
        }

        foreach ($entries as $eachEntry) {
            if (null === $eachEntry->getFieldName() and null === $eachEntry->getObjectIdentityId()) {
                $this->classAces[] = new Entry($eachEntry, $this);
            }

            if (null !== $eachEntry->getFieldName() and null === $eachEntry->getObjectIdentityId()) {
                if (empty($this->classFieldAces[$eachEntry->getFieldName()])) {
                    $this->classFieldAces[$eachEntry->getFieldName()] = array();
                    $this->updateFields($eachEntry->getFieldName());
                }

                $this->classFieldAces[$eachEntry->getFieldName()][] = new FieldEntry($eachEntry, $this);
            }

            if (null === $eachEntry->getFieldName() and null !== $eachEntry->getObjectIdentityId()) {
                $this->objectAces[] = new Entry($eachEntry, $this);
            }

            if (null !== $eachEntry->getFieldName() and null !== $eachEntry->getObjectIdentityId()) {
                if (empty($this->objectFieldAces[$eachEntry->getFieldName()])) {
                    $this->objectFieldAces[$eachEntry->getFieldName()] = array();
                    $this->updateFields($eachEntry->getFieldName());
                }

                $this->objectFieldAces[$eachEntry->getFieldName()][] = new FieldEntry($eachEntry, $this);
            }
        }

        $this->objectIdentity = $objectIdentity;
        $this->permissionGrantingStrategy = $permissionGrantingStrategy;
        $this->parentAcl = $parentAcl;
        $this->inherited = $inherited;
        $this->loadedSecurityIdentities = $loadedSecurityIdentities;

        $this->fields = array_unique($this->fields);
    }

    /**
     * Returns all class-based ACEs associated with this ACL
     *
     * @return array
     */
    public function getClassAces()
    {
        return $this->classAces;
    }

    /**
     * Returns all class-field-based ACEs associated with this ACL
     *
     * @param string $field
     *
     * @return array
     */
    public function getClassFieldAces($field)
    {
        return isset($this->classFieldAces[$field]) ? $this->classFieldAces[$field] : array();
    }

    /**
     * Returns all object-based ACEs associated with this ACL
     *
     * @return array
     */
    public function getObjectAces()
    {
        return $this->objectAces;
    }

    /**
     * Returns all object-field-based ACEs associated with this ACL
     *
     * @param string $field
     *
     * @return array
     */
    public function getObjectFieldAces($field)
    {
        return isset($this->objectFieldAces[$field]) ? $this->objectFieldAces[$field] : array();
    }

    /**
     * Returns the object identity associated with this ACL
     *
     * @return \Symfony\Component\Security\Acl\Model\ObjectIdentityInterface
     */
    public function getObjectIdentity()
    {
        return $this->objectIdentity;
    }

    /**
     * Returns the parent ACL, or null if there is none.
     *
     * @return \Symfony\Component\Security\Acl\Model\AclInterface|null
     */
    public function getParentAcl()
    {
        return $this->parentAcl;
    }

    /**
     * Whether this ACL is inheriting ACEs from a parent ACL.
     *
     * @return bool
     */
    public function isEntriesInheriting()
    {
        return $this->inherited;
    }

    /**
     * Determines whether field access is granted
     *
     * @param string $field
     * @param array  $masks
     * @param array  $securityIdentities
     * @param bool   $administrativeMode
     *
     * @return bool
     */
    public function isFieldGranted($field, array $masks, array $securityIdentities, $administrativeMode = false)
    {
        return $this->permissionGrantingStrategy->isFieldGranted($this, $field, $masks, $securityIdentities, $administrativeMode);
    }

    /**
     * Determines whether access is granted
     *
     * @throws \Symfony\Component\Security\Acl\Exception\NoAceFoundException when no ACE was applicable for this request
     *
     * @param array $masks
     * @param array $securityIdentities
     * @param bool  $administrativeMode
     *
     * @return bool
     */
    public function isGranted(array $masks, array $securityIdentities, $administrativeMode = false)
    {
        return $this->permissionGrantingStrategy->isGranted($this, $masks, $securityIdentities, $administrativeMode);
    }

    /**
     * Whether the ACL has loaded ACEs for all of the passed security identities
     *
     * @throws \InvalidArgumentException
     *
     * @param mixed $securityIdentities an implementation of SecurityIdentityInterface, or an array thereof
     *
     * @return bool
     */
    public function isSidLoaded($securityIdentities)
    {
        if (!is_array($securityIdentities)) {
            $securityIdentities = array($securityIdentities);
        }

        $found = 0;
        foreach ($securityIdentities as $eachSecurityIdentity) {
            if (!$eachSecurityIdentity instanceof SecurityIdentityInterface) {
                throw new \InvalidArgumentException('At least one entry of the given list is not implementing the "SecurityIdentityInterface".');
            }

            foreach ($this->loadedSecurityIdentities as $eachLoadedIdentity) {
                if ($eachSecurityIdentity->equals($eachLoadedIdentity)) {
                    $found++;

                    break;
                }
            }
        }

        return ($found === count($securityIdentities));
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
     * Returns a list of associated fields on this ACL.
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Update the internal list of associated fields on this ACL.
     *
     * @param string $field
     *
     * @return \Propel\PropelBundle\Security\Acl\Domain\Acl $this
     */
    protected function updateFields($field)
    {
        if (!in_array($field, $this->fields)) {
            $this->fields[] = $field;
        }

        return $this;
    }
}
