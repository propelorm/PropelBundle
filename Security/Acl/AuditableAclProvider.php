<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Security\Acl;

use Propel\Runtime\Collection\ObjectCollection;
use Propel\Bundle\PropelBundle\Security\Acl\Domain\AuditableAcl;

use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;

/**
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class AuditableAclProvider extends MutableAclProvider
{
    /**
     * Get an ACL for this provider.
     *
     * @param Propel\Runtime\Collection\ObjectCollection                    $collection
     * @param \Symfony\Component\Security\Acl\Model\ObjectIdentityInterface $objectIdentity
     * @param array                                                         $loadedSecurityIdentities
     * @param \Symfony\Component\Security\Acl\Model\AclInterface            $parentAcl
     * @param bool                                                          $inherited
     *
     * @return \Propel\Bundle\PropelBundle\Security\Acl\Domain\AuditableAcl
     */
    protected function getAcl(ObjectCollection $collection, ObjectIdentityInterface $objectIdentity, array $loadedSecurityIdentities = array(), AclInterface $parentAcl = null, $inherited = true)
    {
        return new AuditableAcl($collection, $objectIdentity, $this->permissionGrantingStrategy, $loadedSecurityIdentities, $parentAcl, $inherited, $this->connection);
    }
}
