<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Tests\Fixtures\Acl;

use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\AclCacheInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;

class ArrayCache implements AclCacheInterface
{
    public $content = array();

    public function evictFromCacheById($primaryKey)
    {
        if (isset($this->content[$primaryKey])) {
            unset($this->content[$primaryKey]);
        }
    }

    public function evictFromCacheByIdentity(ObjectIdentityInterface $oid)
    {
        // Propel ACL does not make use of those.
    }

    public function getFromCacheById($primaryKey)
    {
        if (isset($this->content[$primaryKey])) {
            return $this->content[$primaryKey];
        }

        return null;
    }

    public function getFromCacheByIdentity(ObjectIdentityInterface $oid)
    {
        // Propel ACL does not make use of those.
    }

    public function putInCache(AclInterface $acl)
    {
        if (null === $acl->getId()) {
            throw new \InvalidArgumentException('The given ACL does not have an ID.');
        }

        $this->content[$acl->getId()] = $acl;
    }

    public function clearCache()
    {
        $this->content = array();
    }
}
