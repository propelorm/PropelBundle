<?php
namespace App;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * this class is made to simulate bundle containing base structure so we can work with no bundle architecture used from s4+
 * Class AppBundle
 * @package App
 */
class AppBundle extends Bundle {
    const NAME = 'AppBundle';

    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);

        $this->path = $this->container->get('kernel')->getProjectDir();
    }
}