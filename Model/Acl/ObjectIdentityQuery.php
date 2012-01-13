<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Model\Acl;

use Propel\PropelBundle\Model\Acl\ObjectIdentity;
use Propel\PropelBundle\Model\Acl\om\BaseObjectIdentityQuery;

use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;

class ObjectIdentityQuery extends BaseObjectIdentityQuery
{
    /**
     * Return an ObjectIdentity object belonging to the given ACL related ObjectIdentity.
     *
     * @param ObjectIdentityInterface $objectIdentity
     *
     * @return ObjectIdentity
     */
    public function filterByAclObjectIdentity(ObjectIdentityInterface $objectIdentity)
    {
        $this
            ->useAclClassQuery()
                ->filterByType($objectIdentity->getType())
            ->endUse()
            ->filterByIdentifier($objectIdentity->getIdentifier())
        ;

        return $this;
    }
}
