<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Tests\Model\Acl;

use PropelQuickBuilder;

use Propel\PropelBundle\Model\Acl\AclClass;
use Propel\PropelBundle\Model\Acl\ObjectIdentity as ModelObjectIdentity;

use Propel\PropelBundle\Tests\TestCase as BaseTestCase;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

/**
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class TestCase extends BaseTestCase
{
    protected $con = null;

    public function setUp()
    {
        parent::setUp();

        $this->loadPropelQuickBuilder();

        $schema = file_get_contents(__DIR__.'/../../../Resources/config/acl_schema.xml');

        $builder = new PropelQuickBuilder();
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

    protected function getAclClass()
    {
        return AclClass::fromAclObjectIdentity(new ObjectIdentity(1, 'Propel\PropelBundle\Tests\Fixtures\Model\Book'), $this->con);
    }
}