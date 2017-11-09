<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Model\Acl;

use Propel\PropelBundle\Model\Acl\om\BaseSecurityIdentity;

use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

class SecurityIdentity extends BaseSecurityIdentity
{
    /**
     * Transform a given mode security identity into an ACL related SecurityIdentity.
     *
     * @param \Propel\PropelBundle\Model\Acl\SecurityIdentity $securityIdentity
     *
     * @return \Symfony\Component\Security\Acl\Model\SecurityIdentityInterface
     */
    public static function toAclIdentity(SecurityIdentity $securityIdentity)
    {
        $identifier = $securityIdentity->getIdentifier();

        if ($securityIdentity->getUsername()) {
            if (false === strpos($identifier, '-')) {
                throw new \InvalidArgumentException('The given identifier does not resolve to a UserSecurityIdentity.');
            }

            list($class, $username) = explode('-', $identifier, 2);

            return new UserSecurityIdentity($username, $class);
        }

        if (0 === strpos($identifier, 'ROLE_') or 0 === strpos($identifier, 'IS_AUTHENTICATED_')) {
            return new RoleSecurityIdentity($identifier);
        }

        throw new \InvalidArgumentException('The security identity does not resolve to either UserSecurityIdentity or RoleSecurityIdentity.');
    }

    /**
     * Transform a given ACL security identity into a SecurityIdentity model.
     *
     * If there is no model entry given, a new one will be created and saved to the database.
     *
     * @throws \InvalidArgumentException
     *
     * @param \Symfony\Component\Security\Acl\Model\SecurityIdentityInterface $aclIdentity
     * @param \PropelPDO                                                      $con
     *
     * @return \Propel\PropelBundle\Model\Acl\SecurityIdentity
     */
    public static function fromAclIdentity(SecurityIdentityInterface $aclIdentity, \PropelPDO $con = null)
    {
        if ($aclIdentity instanceof UserSecurityIdentity) {
            $identifier = $aclIdentity->getClass().'-'.$aclIdentity->getUsername();
            $username = true;
        } elseif ($aclIdentity instanceof RoleSecurityIdentity) {
            $identifier = $aclIdentity->getRole();
            $username = false;
        } else {
            throw new \InvalidArgumentException('The ACL identity must either be an instance of UserSecurityIdentity or RoleSecurityIdentity.');
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
