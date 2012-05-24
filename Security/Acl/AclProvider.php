<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Security\Acl;

use Propel\PropelBundle\Model\Acl\EntryQuery;
use Propel\PropelBundle\Model\Acl\ObjectIdentityQuery;
use Propel\PropelBundle\Model\Acl\SecurityIdentity;

use Propel\PropelBundle\Security\Acl\Domain\Acl;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Symfony\Component\Security\Acl\Exception\AclNotFoundException;

use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\AclCacheInterface;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface;

/**
 * An implementation of the AclProviderInterface using Propel ORM.
 *
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class AclProvider implements AclProviderInterface
{
    protected $permissionGrantingStrategy;
    protected $connection;
    protected $cache;

    /**
     * Constructor.
     *
     * @param \Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface $permissionGrantingStrategy
     * @param \PropelPDO                                                                $con
     * @param \Symfony\Component\Security\Acl\Model\AclCacheInterface                   $cache
     */
    public function __construct(PermissionGrantingStrategyInterface $permissionGrantingStrategy, \PropelPDO $connection = null, AclCacheInterface $cache = null)
    {
        $this->permissionGrantingStrategy = $permissionGrantingStrategy;
        $this->connection = $connection;
        $this->cache = $cache;
    }

    /**
     * Retrieves all child object identities from the database.
     *
     * @param \Symfony\Component\Security\Acl\Model\ObjectIdentityInterface $parentObjectIdentity
     * @param bool                                                          $directChildrenOnly
     *
     * @return array
     */
    public function findChildren(ObjectIdentityInterface $parentObjectIdentity, $directChildrenOnly = false)
    {
        $modelIdentity = ObjectIdentityQuery::create()->findOneByAclObjectIdentity($parentObjectIdentity, $this->connection);
        if (empty($modelIdentity)) {
            return array();
        }

        if ($directChildrenOnly) {
            $collection = ObjectIdentityQuery::create()->findChildren($modelIdentity, $this->connection);
        } else {
            $collection = ObjectIdentityQuery::create()->findGrandChildren($modelIdentity, $this->connection);
        }

        $children = array();
        foreach ($collection as $eachChild) {
            $children[] = new ObjectIdentity($eachChild->getIdentifier(), $eachChild->getAclClass($this->connection)->getType());
        }

        return $children;
    }

    /**
     * Returns the ACL that belongs to the given object identity
     *
     * @throws \Symfony\Component\Security\Acl\Exception\AclNotFoundException
     *
     * @param \Symfony\Component\Security\Acl\Model\ObjectIdentityInterface $objectIdentity
     * @param array                                                         $securityIdentities
     *
     * @return \Symfony\Component\Security\Acl\Model\AclInterface
     */
    public function findAcl(ObjectIdentityInterface $objectIdentity, array $securityIdentities = array())
    {
        $modelObj = ObjectIdentityQuery::create()->findOneByAclObjectIdentity($objectIdentity, $this->connection);
        if (null !== $this->cache and null !== $modelObj) {
            $cachedAcl = $this->cache->getFromCacheById($modelObj->getId());
            if ($cachedAcl instanceof AclInterface) {
                return $cachedAcl;
            }
        }

        $collection = EntryQuery::create()->findByAclIdentity($objectIdentity, $securityIdentities, $this->connection);

        if (0 === count($collection)) {
            if (empty($securityIdentities)) {
                $errorMessage = 'There is no ACL available for this object identity. Please create one using the MutableAclProvider.';
            } else {
                $errorMessage = 'There is at least no ACL for this object identity and the given security identities. Try retrieving the ACL without security identity filter and add ACEs for the security identities.';
            }

            throw new AclNotFoundException($errorMessage);
        }

        $loadedSecurityIdentities = array();
        foreach ($collection as $eachEntry) {
            if (!isset($loadedSecurityIdentities[$eachEntry->getSecurityIdentity()->getId()])) {
                $loadedSecurityIdentities[$eachEntry->getSecurityIdentity()->getId()] = SecurityIdentity::toAclIdentity($eachEntry->getSecurityIdentity());
            }
        }

        $parentAcl = null;
        $entriesInherited = true;

        if (null !== $modelObj) {
            $entriesInherited = $modelObj->getEntriesInheriting();
            if (null !== $modelObj->getParentObjectIdentityId()) {
                $parentObj = $modelObj->getObjectIdentityRelatedByParentObjectIdentityId($this->connection);
                try {
                    $parentAcl = $this->findAcl(new ObjectIdentity($parentObj->getIdentifier(), $parentObj->getAclClass($this->connection)->getType()));
                } catch (AclNotFoundException $e) {
                    /*
                     *  This happens e.g. if the parent ACL is created, but does not contain any ACE by now.
                     *  The ACEs may be applied later on.
                     */
                }
            }
        }

        return $this->getAcl($collection, $objectIdentity, $loadedSecurityIdentities, $parentAcl, $entriesInherited);
    }

    /**
     * Returns the ACLs that belong to the given object identities
     *
     * @throws \Symfony\Component\Security\Acl\Exception\AclNotFoundException When at least one object identity is missing its ACL.
     *
     * @param array $objectIdentities   an array of ObjectIdentityInterface implementations
     * @param array $securityIdentities an array of SecurityIdentityInterface implementations
     *
     * @return \SplObjectStorage mapping the passed object identities to ACLs
     */
    public function findAcls(array $objectIdentities, array $securityIdentities = array())
    {
        $result = new \SplObjectStorage();
        foreach ($objectIdentities as $eachIdentity) {
            $result[$eachIdentity] = $this->findAcl($eachIdentity, $securityIdentities);
        }

        return $result;
    }

    /**
     * Create an ACL.
     *
     * @param \PropelObjectCollection                                       $collection
     * @param \Symfony\Component\Security\Acl\Model\ObjectIdentityInterface $objectIdentity
     * @param array                                                         $loadedSecurityIdentities
     * @param \Symfony\Component\Security\Acl\Model\AclInterface            $parentAcl
     * @param bool                                                          $inherited
     *
     * @return \Propel\PropelBundle\Security\Acl\Domain\Acl
     */
    protected function getAcl(\PropelObjectCollection $collection, ObjectIdentityInterface $objectIdentity, array $loadedSecurityIdentities = array(), AclInterface $parentAcl = null, $inherited = true)
    {
        return new Acl($collection, $objectIdentity, $this->permissionGrantingStrategy, $loadedSecurityIdentities, $parentAcl, $inherited);
    }
}
