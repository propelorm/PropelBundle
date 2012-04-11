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
        $this->fixtures_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'propel';
        $this->fixtures_files = array(
            '10_foo.yml', '20_bar.yml', '15_biz.yml', '18_boo.sql', '42_baz.sql'
        );

        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->fixtures_dir);
        foreach ($this->fixtures_files as $file)
        {
            $this->filesystem->touch($this->fixtures_dir . DIRECTORY_SEPARATOR . $file);
        }
    }

    public function tearDown()
    {
        $this->filesystem->remove($this->fixtures_dir);
    }

    public function testOrderedFixturesFiles()
    {
        $this->assertEquals(
            array('10_foo.yml', '15_biz.yml', '20_bar.yml',),
            $this->cleanFixtureIterator($this->command->getFixtureFiles('yml', $this->fixtures_dir))
        );

        $this->assertEquals(
            array('18_boo.sql', '42_baz.sql',),
            $this->cleanFixtureIterator($this->command->getFixtureFiles('sql', $this->fixtures_dir))
        );
    }

    protected function cleanFixtureIterator($file_iterator)
    {
        $tmp_dir = $this->fixtures_dir;

        return array_map(function($file) use($tmp_dir) {
            return str_replace($tmp_dir . DIRECTORY_SEPARATOR, '', $file);
        }, array_keys(iterator_to_array($file_iterator)));
    }
}

class TestableFixturesLoadCommand extends FixturesLoadCommand
{
    public function getFixtureFiles($type = 'sql', $in = null)
    {
        return parent::getFixtureFiles($type, $in);
    }
}
