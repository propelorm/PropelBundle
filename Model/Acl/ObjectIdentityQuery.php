<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Model\Acl;

use Criteria;
use PropelPDO;
use PropelCollection;

use Propel\PropelBundle\Model\Acl\ObjectIdentity;
use Propel\PropelBundle\Model\Acl\om\BaseObjectIdentityQuery;

use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;

class ObjectIdentityQuery extends BaseObjectIdentityQuery
{
    /**
     * Filter by an ObjectIdentity object belonging to the given ACL related ObjectIdentity.
     *
     * @param ObjectIdentityInterface $objectIdentity
     *
     * @return ObjectIdentityQuery $this
     */
    public function filterByAclObjectIdentity(ObjectIdentityInterface $objectIdentity)
    {
        /*
         * Not using a JOIN here, because the filter may be applied on 'findOneOrCreate',
         * which is currently (Propel 1.6.4-dev) not working.
         */
        $aclClass = AclClass::fromAclObjectIdentity($objectIdentity);
        $this
            ->filterByClassId($aclClass->getId())
            ->filterByIdentifier($objectIdentity->getIdentifier())
        ;

        return $this;
    }

    /**
     * Return an ObjectIdentity object belonging to the given ACL related ObjectIdentity.
     *
     * @param ObjectIdentityInterface $objectIdentity
     * @param PropelPDO $con
     *
     * @return ObjectIdentity
     */
    public function findOneByAclObjectIdentity(ObjectIdentityInterface $objectIdentity, PropelPDO $con = null)
    {
        return $this
            ->filterByAclObjectIdentity($objectIdentity)
            ->findOne($con)
        ;
    }

    /**
     * Return all children of the given object identity.
     *
     * @param ObjectIdentity $objectIdentity
     * @param PropelPDO $con
     *
     * @return PropelCollection
     */
    public function findChildren(ObjectIdentity $objectIdentity, PropelPDO $con = null)
    {
        return $this
            ->filterByObjectIdentityRelatedByParentObjectIdentityId($objectIdentity)
            ->find($con)
        ;
    }

    /**
     * Return all children and grand-children of the given object identity.
     *
     * @param ObjectIdentity $objectIdentity
     * @param PropelPDO $con
     *
     * @return PropelCollection
     */
    public function findGrandChildren(ObjectIdentity $objectIdentity, PropelPDO $con = null)
    {
        return $this
            ->useObjectIdentityAncestorRelatedByObjectIdentityIdQuery()
                ->filterByObjectIdentityRelatedByAncestorId($objectIdentity)
                ->filterByObjectIdentityRelatedByObjectIdentityId($objectIdentity, Criteria::NOT_EQUAL)
            ->endUse()
            ->find($con)
        ;
    }
}
