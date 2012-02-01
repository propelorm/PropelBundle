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

use Propel\PropelBundle\Tests\TestCase as BaseTestCase;

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
}