<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Security\Acl\Domain;

use InvalidArgumentException;
use PropelCollection;

use Propel\PropelBundle\Model\Acl\SecurityIdentity;

use Symfony\Component\Security\Acl\Exception\Exception as AclException;

use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

/**
 * An ACL implementation that is immutable based on data from a PropelCollection of Propel\PropelBundle\Model\Acl\Entry.
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
     * Constructor.
     *
     * @param PropelCollection $entries
     * @param ObjectIdentityInterface $objectIdentity
     * @param PermissionGrantingStrategyInterface $permissionGrantingStrategy
     * @param array $loadedSecurityIdentities
     * @param AclInterface $parentAcl
     * @param boolean $inherited
     */
    public function __construct(PropelCollection $entries, ObjectIdentityInterface $objectIdentity, PermissionGrantingStrategyInterface $permissionGrantingStrategy, array $loadedSecurityIdentities = array(), AclInterface $parentAcl = null, $inherited = false)
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
                }

                $this->classFieldAces[$eachEntry->getFieldName()][] = new FieldEntry($eachEntry, $this);
            }

            if (null === $eachEntry->getFieldName() and null !== $eachEntry->getObjectIdentityId()) {
                $this->objectAces[] = new Entry($eachEntry, $this);
            }

            if (null !== $eachEntry->getFieldName() and null !== $eachEntry->getObjectIdentityId()) {
                if (empty($this->objectFieldAces[$eachEntry->getFieldName()])) {
                    $this->objectFieldAces[$eachEntry->getFieldName()] = array();
                }

                $this->objectFieldAces[$eachEntry->getFieldName()][] = new FieldEntry($eachEntry, $this);
            }
        }

        $this->objectIdentity = $objectIdentity;
        $this->permissionGrantingStrategy = $permissionGrantingStrategy;
        $this->parentAcl = $parentAcl;
        $this->inherited = $inherited;
        $this->loadedSecurityIdentities = $loadedSecurityIdentities;
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
     * @return ObjectIdentityInterface
     */
    public function getObjectIdentity()
    {
        return $this->objectIdentity;
    }

    /**
     * Returns the parent ACL, or null if there is none.
     *
     * @return AclInterface|null
     */
    public function getParentAcl()
    {
        return $this->parentAcl;
    }

    /**
     * Whether this ACL is inheriting ACEs from a parent ACL.
     *
     * @return boolean
     */
    public function isEntriesInheriting()
    {
        return $this->inherited;
    }

    /**
     * Determines whether field access is granted
     *
     * @param string  $field
     * @param array   $masks
     * @param array   $securityIdentities
     * @param boolean $administrativeMode
     *
     * @return boolean
     */
    public function isFieldGranted($field, array $masks, array $securityIdentities, $administrativeMode = false)
    {
        return $this->permissionGrantingStrategy->isFieldGranted($this, $field, $masks, $securityIdentities, $administrativeMode);
    }

    /**
     * Determines whether access is granted
     *
     * @throws NoAceFoundException when no ACE was applicable for this request
     *
     * @param array   $masks
     * @param array   $securityIdentities
     * @param boolean $administrativeMode
     *
     * @return boolean
     */
    public function isGranted(array $masks, array $securityIdentities, $administrativeMode = false)
    {
        return $this->permissionGrantingStrategy->isGranted($this, $masks, $securityIdentities, $administrativeMode);
    }

    /**
     * Whether the ACL has loaded ACEs for all of the passed security identities
     *
     * @throws InvalidArgumentException
     *
     * @param mixed $securityIdentities an implementation of SecurityIdentityInterface, or an array thereof
     *
     * @return boolean
     */
    public function isSidLoaded($securityIdentities)
    {
        if (!is_array($securityIdentities)) {
            $securityIdentities = array($securityIdentities);
        }

        $found = 0;

        foreach ($securityIdentities as $eachSecurityIdentity) {
            if (!$eachSecurityIdentity instanceof SecurityIdentityInterface) {
                throw new InvalidArgumentException('At least one entry of the given list is not implementing the "SecurityIdentityInterface".');
            }

            $modelIdentity = SecurityIdentity::fromAclIdentity($eachSecurityIdentity);
            foreach ($this->loadedSecurityIdentities as $id => $eachLoadedIdentity) {
                if ($id === $modelIdentity->getId()) {
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
}
