<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Tests\Fixtures;

use Symfony\Component\Security\Core\User\UserInterface;

class UserProxy implements UserInterface
{
    protected $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function getRoles()
    {
        $roles = $this->getPropelUser()->getRoles();
    }

    public function getPassword()
    {
        return $this->getPropelUser()->getPassword();
    }

    public function getSalt()
    {
        return $this->getPropelUser()->getSalt();
    }

    public function getUsername()
    {
        return $this->getPropelUser()->getUsername();
    }

    public function eraseCredentials()
    {
    }

    public function equals(UserInterface $user)
    {
        return $this->getPropelUser()->equals($user);
    }

    public function getAlgorithm()
    {
        return $this->getPropelUser()->getAlgorithm();
    }

    public function __call($method, $arguments)
    {
        if (is_callable(array($this->user, $method))) {
            return call_user_func_array(array($this->user, $method), $arguments);
        }

        throw new \BadMethodCallException('Can\'t call method '.$method);
    }

    public function getPropelUser()
    {
        return $this->user;
    }
}
