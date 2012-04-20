<?php

namespace Propel\PropelBundle\Tests\Fixtures\Model;

use Propel\PropelBundle\Tests\Fixtures\Model\om\BaseUser;
use Symfony\Component\Security\Core\User\UserInterface;

class User extends BaseUser implements UserInterface
{
    public function eraseCredentials()
    {
    }
}
