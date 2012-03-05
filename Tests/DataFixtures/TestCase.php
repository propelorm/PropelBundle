<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Tests\DataFixtures;

use Propel\PropelBundle\Tests\TestCase as BaseTestCase;

/**
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class TestCase extends BaseTestCase
{
    /**
     * @var \PropelPDO
     */
    protected $con;

    protected function setUp()
    {
        parent::setUp();

        $this->loadPropelQuickBuilder();

        $schema = <<<XML
<database name="default" package="vendor.bundles.Propel.PropelBundle.Tests.Fixtures.DataFixtures.Loader" namespace="Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader" defaultIdMethod="native">
    <table name="book">
        <column name="id" type="integer" primaryKey="true" />
        <column name="name" type="varchar" size="255" />
        <column name="author_id" type="integer" required="false" defaultValue="null" />

        <foreign-key foreignTable="book_author" onDelete="RESTRICT" onUpdate="CASCADE">
            <reference local="author_id" foreign="id" />
        </foreign-key>
    </table>

    <table name="book_author">
        <column name="id" type="integer" primaryKey="true" />
        <column name="name" type="varchar" size="255" />
    </table>
</database>
XML;

        $builder = new \PropelQuickBuilder();
        $builder->setSchema($schema);
        if (!class_exists('Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\Book')) {
            $builder->setClassTargets(array('peer', 'object', 'query', 'peerstub', 'objectstub', 'querystub'));
        } else {
            $builder->setClassTargets(array());
        }

        $this->con = $builder->build();
    }
}
