<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Tests;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * TestCase
 */
class TestCase extends \PHPUnit_Framework_TestCase
{
    public function getContainer()
    {
        $container = new ContainerBuilder(new ParameterBag(array(
            'kernel.debug'      => false,
            'kernel.root_dir'   => __DIR__ . '/../',
        )));

        $container->setParameter('propel.configuration', array());
        $container->setDefinition('propel', new Definition('Propel\Runtime\Propel'));

        return $container;
    }
}
