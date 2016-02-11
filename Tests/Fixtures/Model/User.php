<?php

namespace Propel\Bundle\PropelBundle\Tests\Fixtures\Model;

use Propel\Bundle\PropelBundle\Tests\Fixtures\Model\Base\User as BaseUser;
use Symfony\Component\Security\Core\User\UserInterface;

class User extends BaseUser implements UserInterface
{
    public function eraseCredentials()
    {
    }
}
