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

    /**
     * The list of created temp files to be removed.
     *
     * @var array
     */
    protected $tmpFiles = array();

    protected function setUp()
    {
        parent::setUp();

        $this->loadPropelQuickBuilder();

        $schema = <<<XML
<database name="default" package="vendor.bundles.Propel.PropelBundle.Tests.Fixtures.DataFixtures.Loader" namespace="Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader" defaultIdMethod="native">
    <table name="book">
        <column name="id" type="integer" primaryKey="true" />
        <column name="name" type="varchar" size="255" />
        <column name="description" type="varchar" />
        <column name="author_id" type="integer" required="false" defaultValue="null" />
        <column name="complementary_infos" required="false" type="object" description="An object column" />

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
        if (class_exists('Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\Book')) {
            $builder->setClassTargets(array());
        }

        $this->con = $builder->build();
    }

    protected function tearDown()
    {
        foreach ($this->tmpFiles as $eachFile) {
            @unlink($eachFile);
        }

        $this->tmpFiles = array();
    }

    /**
     * Return the name of a created temporary file containing the given content.
     *
     * @param string $content
     *
     * @return string
     */
    protected function getTempFile($content = '')
    {
        $filename = tempnam(sys_get_temp_dir(), 'propelbundle-datafixtures-test');
        @unlink($filename);

        file_put_contents($filename, $content);

        $this->tmpFiles[] = $filename;

        return $filename;
    }
}
