<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Tests;

use Propel\Generator\Util\QuickBuilder;

use Propel\Bundle\PropelBundle\Model\Acl\AclClass;
use Propel\Bundle\PropelBundle\Model\Acl\Entry;
use Propel\Bundle\PropelBundle\Model\Acl\ObjectIdentity as ModelObjectIdentity;
use Propel\Bundle\PropelBundle\Security\Acl\MutableAclProvider;

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

        $schema = file_get_contents(__DIR__.'/../Resources/acl_schema.xml');

        if (!class_exists('Propel\Bundle\PropelBundle\Model\Acl\Map\AclClassTableMap')) {
            $classTargets = array('tablemap', 'object', 'query');
        } else {
            $classTargets = array();
        }

        $builder = new QuickBuilder();
        $builder->setSchema($schema);

        $this->con = $builder->build($dsn = null, $user = null, $pass = null, $adapter = null, $classTargets);
    }

    /**
     * @return \Propel\Bundle\PropelBundle\Model\Acl\ObjectIdentity
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
        return new ObjectIdentity($identifier, 'Propel\Bundle\PropelBundle\Tests\Fixtures\Model\Book');
    }

    protected function getRoleSecurityIdentity($role = 'ROLE_USER')
    {
        return new RoleSecurityIdentity(new Role($role));
    }
}
