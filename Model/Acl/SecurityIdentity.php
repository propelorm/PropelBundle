<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Model\Acl;

use InvalidArgumentException;
use PropelPDO;

use Propel\PropelBundle\Model\Acl\om\BaseSecurityIdentity;

use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

class SecurityIdentity extends BaseSecurityIdentity
{
    /**
     * Transform a given mode security identity into an ACL related SecurityIdentity.
     *
     * @param SecurityIdentity $securityIdentity
     *
     * @return SecurityIdentityInterface
     */
    public static function toAclIdentity(SecurityIdentity $securityIdentity)
    {
        if ($securityIdentity->getUsername()) {
            list($class, $username) = explode('-', $securityIdentity->getIdentifier());

            return new UserSecurityIdentity($username, $class);
        }

        if (0 === strpos($securityIdentity->getIdentifier(), 'ROLE_') or 0 === strpos($securityIdentity->getIdentifier(), 'IS_AUTHENTICATED_')) {
            return new RoleSecurityIdentity($securityIdentity->getIdentifier());
        }

        throw new InvalidArgumentException('The security identity does not resolve to either UserSecurityIdentity or RoleSecurityIdentity.');
    }

    /**
     * Transform a given ACL security identity into a SecurityIdentity model.
     *
     * If there is no model entry given, a new one will be created and saved to the database.
     *
     * @throws \InvalidArgumentException
     *
     * @param SecurityIdentityInterface $aclIdentity
     * @param PropelPDO $con
     *
     * @return SecurityIdentity
     */
    public static function fromAclIdentity(SecurityIdentityInterface $aclIdentity, PropelPDO $con = null)
    {
        if ($aclIdentity instanceof UserSecurityIdentity) {
            $identifier = $aclIdentity->getClass().'-'.$aclIdentity->getUsername();
            $username = true;
        } elseif ($aclIdentity instanceof RoleSecurityIdentity) {
            $identifier = $aclIdentity->getRole();
            $username = false;
        } else {
            throw new InvalidArgumentException('The ACL identity must either be an instance of UserSecurityIdentity or RoleSecurityIdentity.');
        }

        $obj = SecurityIdentityQuery::create()
            ->filterByIdentifier($identifier)
            ->filterByUsername($username)
            ->findOneOrCreate($con)
        ;

        if ($obj->isNew()) {
            $obj->save($con);
        }

        return $obj;
    }
}
