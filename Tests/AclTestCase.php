<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Tests;

use Propel\PropelBundle\Model\Acl\AclClass;
use Propel\PropelBundle\Model\Acl\Entry;
use Propel\PropelBundle\Model\Acl\ObjectIdentity as ModelObjectIdentity;
use Propel\PropelBundle\Security\Acl\MutableAclProvider;

use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\PermissionGrantingStrategy;

/**
 * AclTestCase
 *
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class AclTestCase extends TestCase
{
    protected $con = null;
    protected $cache = null;

    public function setUp()
    {
        parent::setUp();

        $this->loadPropelQuickBuilder();

        $schema = file_get_contents(__DIR__.'/../Resources/acl_schema.xml');

        $builder = new \PropelQuickBuilder();
        $builder->setSchema($schema);
        if (!class_exists('Propel\PropelBundle\Model\Acl\map\AclClassTableMap')) {
            $builder->setClassTargets(array('tablemap', 'peer', 'object', 'query'));
        } else {
            $builder->setClassTargets(array());
        }

        $this->con = $builder->build();
    }

    /**
     * @return \Propel\PropelBundle\Model\Acl\ObjectIdentity
     */
    protected function createModelObjectIdentity($identifier)
    {
        $aclClass = $this->getAclClass();
        $objIdentity = new ModelObjectIdentity();

        $this->assertTrue((bool) $objIdentity
            ->setAclClass($aclClass)
            ->setIdentifier($identifier)
            ->save($this->con)
        );

        return $objIdentity;
    }

    protected function createEntry()
    {
        $entry = new Entry();
        $entry
            ->setAuditSuccess(false)
            ->setAuditFailure(false)
            ->setMask(64)
            ->setGranting(true)
            ->setGrantingStrategy('all')
            ->setAceOrder(0)
        ;

        return $entry;
    }

    protected function getAclClass()
    {
        return AclClass::fromAclObjectIdentity($this->getAclObjectIdentity(), $this->con);
    }

    protected function getAclProvider()
    {
        return new MutableAclProvider(new PermissionGrantingStrategy(), $this->con, $this->cache);
    }

    protected function getAclObjectIdentity($identifier = 1)
    {
        return new ObjectIdentity($identifier, 'Propel\PropelBundle\Tests\Fixtures\Model\Book');
    }

    protected function getRoleSecurityIdentity($role = 'ROLE_USER')
    {
        return new RoleSecurityIdentity(new Role($role));
    }
}
