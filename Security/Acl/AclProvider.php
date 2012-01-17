<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Security\Acl;

use PropelPDO;

use Propel\PropelBundle\Model\Acl\EntryQuery;
use Propel\PropelBundle\Model\Acl\ObjectIdentityQuery;
use Propel\PropelBundle\Model\Acl\SecurityIdentity;

use Propel\PropelBundle\Security\Acl\Domain\Acl;
use Propel\PropelBundle\Security\Acl\Domain\Entry;
use Propel\PropelBundle\Security\Acl\Domain\FieldEntry;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;

use Symfony\Component\Security\Acl\Exception\AclNotFoundException;

use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\AclCacheInterface;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface;

/**
 * An implementation of the AclProviderInterface using Propel ORM.
 *
 * @todo Add handling of AclCacheInterface.
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
     * @param PermissionGrantingStrategyInterface $permissionGrantingStrategy
     * @param PropelPDO $con
     * @param AclCacheInterface $cache
     */
    public function __construct(PermissionGrantingStrategyInterface $permissionGrantingStrategy, PropelPDO $connection = null, AclCacheInterface $cache = null)
    {
        $this->permissionGrantingStrategy = $permissionGrantingStrategy;
        $this->connection = $connection;
        $this->cache = $cache;
    }

    /**
     * Retrieves all child object identities from the database.
     *
     * @param ObjectIdentityInterface $parentObjectIdentity
     * @param boolean $directChildrenOnly
     *
     * @return array
     */
    public function findChildren(ObjectIdentityInterface $parentObjectIdentity, $directChildrenOnly = false)
    {
        $modelIdentity = ObjectIdentityQuery::create()
            ->filterByAclObjectIdentity($parentObjectIdentity)
            ->findOne($this->connection)
        ;

        if (empty($modelIdentity)) {
            return array();
        }

        $children = array();

        if ($directChildrenOnly) {
            $collection = ObjectIdentityQuery::create()
                ->filterByObjectIdentityRelatedByParentObjectIdentityId($modelIdentity)
                ->find($this->connection)
            ;
        } else {
            $collection = ObjectIdentityQuery::create()
                ->useObjectIdentityAncestorRelatedByAncestorIdQuery()
                    ->filterByObjectIdentityRelatedByAncestorId($modelIdentity)
                ->endUse()
                ->find($this->connection)
            ;
        }

        foreach ($collection as $eachChild) {
            $children[] = new ObjectIdentity($eachChild->getIdentifier(), $eachChild->getAclClass()->getType());
        }

        return $children;
    }

    /**
     * Returns the ACL that belongs to the given object identity
     *
     * @throws AclNotFoundException
     *
     * @param ObjectIdentityInterface $objectIdentity
     * @param array $securityIdentities
     *
     * @return AclInterface
     */
    public function findAcl(ObjectIdentityInterface $objectIdentity, array $securityIdentities = array())
    {
        $collection = EntryQuery::create()->findByAclIdentity($objectIdentity, $securityIdentities);

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

        return new Acl($collection, $objectIdentity, $this->permissionGrantingStrategy, $loadedSecurityIdentities);
    }

    /**
     * Returns the ACLs that belong to the given object identities
     *
     * @throws AclNotFoundException When at least one object identity is missing its ACL.
     *
     * @param array $objectIdentities an array of ObjectIdentityInterface implementations
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
}
