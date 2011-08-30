<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Tests;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * TestCase
 *
 * @author William DURAND <william.durand1@gmail.com>
 */
class TestCase extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!file_exists($file = $this->getContainer()->getParameter('kernel.root_dir').'/../vendor/propel/runtime/lib/Propel.php')) {
            $this->markTestSkipped('Propel is not available.');
        }

        require_once $file;
    }

    public function getContainer()
    {
        return new ContainerBuilder(new ParameterBag(array(
            'kernel.debug'  => false,
            'kernel.root_dir' => __DIR__.'/../../../../',
        )));
    }
}
