<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */
namespace Propel\Bundle\PropelBundle\DataFixtures\Loader;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * YAML fixtures loader.
 *
 * @author William Durand <william.durand1@gmail.com>
 */
class YamlDataLoader extends AbstractDataLoader
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function __construct($rootDir, ContainerInterface $container = null)
    {
        parent::__construct($rootDir);

        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    protected function transformDataToArray($file)
    {
        if (strpos($file, "\n") === false && is_file($file)) {
            if (false === is_readable($file)) {
                throw new ParseException(sprintf('Unable to parse "%s" as the file is not readable.', $file));
            }

            if (null !== $this->container && $this->container->has('faker.generator')) {
                $generator = $this->container->get('faker.generator');
                $faker = function($type) use ($generator) {
                    $args = func_get_args();
                    array_shift($args);

                    $value = call_user_func_array(array($generator, $type), $args);
                    if ($value instanceof \DateTime) {
                        $value = $value->format('Y-m-d H:i:s');
                    }

                    echo Yaml::dump($value) . "\n";
                };
            } else {
                $faker = function($text) {
                    echo $text . "\n";
                };
            }

            ob_start();
            $retval  = include($file);
            $content = ob_get_clean();

            // if an array is returned by the config file assume it's in plain php form else in YAML
            $file = is_array($retval) ? $retval : $content;

            // if an array is returned by the config file assume it's in plain php form else in YAML
            if (is_array($file)) {
                return $file;
            }
        }

        return Yaml::parse($file);
    }
}
