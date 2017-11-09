<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Tests\Command;

use Symfony\Component\Filesystem\Filesystem;

use Propel\PropelBundle\Tests\TestCase;
use Propel\PropelBundle\Command\FixturesLoadCommand;

/**
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
class FixturesLoadCommandTest extends TestCase
{
    protected $command;

    public function setUp()
    {
        $this->command = new TestableFixturesLoadCommand('testable-command');

        // let's create some dummy fixture files
        $this->fixturesDir   = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'propel';
        $this->fixturesFiles = array(
            '10_foo.yml', '20_bar.yml', '15_biz.yml', '18_boo.sql', '42_baz.sql'
        );

        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->fixturesDir);

        $this->fixturesDir = realpath($this->fixturesDir);

        foreach ($this->fixturesFiles as $file) {
            $this->filesystem->touch($this->fixturesDir . DIRECTORY_SEPARATOR . $file);
        }
    }

    public function tearDown()
    {
        $this->filesystem->remove($this->fixturesDir);
    }

    public function testOrderedFixturesFiles()
    {
        $this->assertEquals(
            array('10_foo.yml', '15_biz.yml', '20_bar.yml',),
            $this->cleanFixtureIterator($this->command->getFixtureFiles('yml', $this->fixturesDir))
        );

        $this->assertEquals(
            array('18_boo.sql', '42_baz.sql',),
            $this->cleanFixtureIterator($this->command->getFixtureFiles('sql', $this->fixturesDir))
        );
    }

    protected function cleanFixtureIterator($file_iterator)
    {
        $tmpDir = realpath($this->fixturesDir);

        return array_map(function($file) use ($tmpDir) {
            return str_replace($tmpDir . DIRECTORY_SEPARATOR, '', $file);
        }, array_values(iterator_to_array($file_iterator)));
    }
}

class TestableFixturesLoadCommand extends FixturesLoadCommand
{
    public function getFixtureFiles($type = 'sql', $in = null)
    {
        return parent::getFixtureFiles($type, $in);
    }
}
