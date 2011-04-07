<?php

namespace Propel\PropelBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * PropelBundle
 *
 * @author William DURAND <william.durand1@gmail.com>
 */
class PropelBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        require_once $this->container->getParameter('propel.path').'/runtime/lib/Propel.php';

        if (0 === strncasecmp(PHP_SAPI, 'cli', 3)) {
            set_include_path($this->container->getParameter('propel.phing_path').'/classes'.PATH_SEPARATOR.get_include_path());
        }

        if (!\Propel::isInit()) {
            \Propel::setConfiguration($this->container->get('propel.configuration'));

            if ($this->container->getParameter('propel.logging')) {
                $this
                    ->container
                    ->get('propel.configuration')
                    ->setParameter('debugpdo.logging.details', array(
                        'time' => array('enabled' => true),
                        'mem'  => array('enabled' => true),
                    ));

                \Propel::setLogger($this->container->get('propel.logger'));
            }

            \Propel::initialize();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespace()
    {
        return __NAMESPACE__;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return __DIR__;
    }
}
