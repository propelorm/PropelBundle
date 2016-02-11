<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Tests\DataFixtures;

use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\Propel;
use Propel\Bundle\PropelBundle\Tests\TestCase as BaseTestCase;

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

        if (!class_exists('Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\CoolBook')) {
            $schema = <<<XML
<database name="default" package="vendor.bundles.Propel.Bundle.PropelBundle.Tests.Fixtures.DataFixtures.Loader" namespace="Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader" defaultIdMethod="native">
    <table name="cool_book">
        <column name="id" type="integer" primaryKey="true" />
        <column name="name" type="varchar" size="255" />
        <column name="description" type="varchar" />
        <column name="author_id" type="integer" required="false" defaultValue="null" />
        <column name="complementary_infos" required="false" type="object" description="An object column" />

        <foreign-key foreignTable="cool_book_author" onDelete="CASCADE" onUpdate="CASCADE">
            <reference local="author_id" foreign="id" />
        </foreign-key>
    </table>

    <table name="cool_book_author">
        <column name="id" type="integer" primaryKey="true" />
        <column name="name" type="varchar" size="255" />
    </table>
</database>
XML;

            QuickBuilder::buildSchema($schema);
        }

        $this->con = Propel::getServiceContainer()->getConnection('default');
        $this->con->beginTransaction();
    }

    protected function tearDown()
    {
        foreach ($this->tmpFiles as $eachFile) {
            @unlink($eachFile);
        }

        $this->tmpFiles = array();

        // Only commit if the transaction hasn't failed.
        // This is because tearDown() is also executed on a failed tests,
        // and we don't want to call ConnectionInterface::commit() in that case
        // since it will trigger an exception on its own
        // ('Cannot commit because a nested transaction was rolled back')
        if (null !== $this->con) {
            if ($this->con->isCommitable()) {
                $this->con->commit();
            }
            $this->con = null;
        }
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
