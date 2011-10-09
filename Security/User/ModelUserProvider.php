<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Security\User;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

/**
 * ModelUserProvider class.
 *
 * Provides easy to use provisioning for Propel model users.
 *
 * @author William DURAND <william.durand1@gmail.com>
 */
class ModelUserProvider implements UserProviderInterface
{
    /**
     * A Model class name.
     * @var string
     */
    protected $class;
    /**
     * A Query class name.
     * @var string
     */
    protected $queryClass;
    /**
     * @var string
     */
    protected $property;

    /**
     * Default constructor
     *
     * @param $class    The User model class.
     * @param $property The property to use to retrieve a user.
     */
    public function __construct($class, $property = null)
    {
        $this->class = $class;
        $this->queryClass = $class.'Query';
        $this->property = $property;
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username)
    {
        $queryClass = $this->queryClass;

        $query = $queryClass::create();

        if (null !== $property) {
            $filter = 'filterBy' . ucfirst($property);
            $query->$filter($username);
        } else {
            $query->filterByUsername($username);
        }

        $user = $query->findOne();

        if (null === $user) {
            throw new UsernameNotFoundException(sprintf('User "%s" not found.', $username));
        }

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user)
    {
        if ($user instanceof $this->class) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return $class === $this->class;
    }
}
