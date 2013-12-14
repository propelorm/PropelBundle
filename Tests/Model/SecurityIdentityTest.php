<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Tests\Model\Acl;

use Propel\PropelBundle\Model\Acl\SecurityIdentity;
use Propel\PropelBundle\Model\Acl\SecurityIdentityQuery;

use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;

use Propel\PropelBundle\Tests\AclTestCase;

/**
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class SecurityIdentityTest extends AclTestCase
{
    public function testToAclIdentityUserWithInvalidIdentifier()
    {
        $identity = new SecurityIdentity();
        $identity->setIdentifier('invalidIdentifier');
        $identity->setUsername(true);

        $this->setExpectedException('InvalidArgumentException');
        SecurityIdentity::toAclIdentity($identity);
    }

    public function testToAclIdentityUnknownSecurityIdentity()
    {
        $identity = new SecurityIdentity();
        $identity->setIdentifier('invalidIdentifier');
        $identity->setUsername(false);

        $this->setExpectedException('InvalidArgumentException');
        SecurityIdentity::toAclIdentity($identity);
    }

    public function testToAclIdentityValidUser()
    {
        $identity = new SecurityIdentity();
        $identity->setIdentifier('Propel\PropelBundle\Tests\Fixtures\UserProxy-propel');
        $identity->setUsername(true);

        $secIdentity = SecurityIdentity::toAclIdentity($identity);
        $this->assertInstanceOf('Symfony\Component\Security\Acl\Domain\UserSecurityIdentity', $secIdentity);
    }

    public function testToAclIdentityMultipleDashes()
    {
        $identity = new SecurityIdentity();
        $identity->setIdentifier('Propel\PropelBundle\Tests\Fixtures\UserProxy-some-username@domain.com');
        $identity->setUsername(true);

        $secIdentity = SecurityIdentity::toAclIdentity($identity);
        $this->assertInstanceOf('Symfony\Component\Security\Acl\Domain\UserSecurityIdentity', $secIdentity);
        $this->assertEquals('some-username@domain.com', $secIdentity->getUsername());
    }

    public function testToAclIdentityValidRole()
    {
        $identity = new SecurityIdentity();
        $identity->setIdentifier('ROLE_ADMIN');
        $identity->setUsername(false);

        $secIdentity = SecurityIdentity::toAclIdentity($identity);
        $this->assertInstanceOf('Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity', $secIdentity);

        $identity = new SecurityIdentity();
        $identity->setIdentifier('IS_AUTHENTICATED_ANONYMOUSLY');
        $identity->setUsername(false);

        $secIdentity = SecurityIdentity::toAclIdentity($identity);
        $this->assertInstanceOf('Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity', $secIdentity);
    }

    public function testFromAclIdentityWithInvalid()
    {
        $secIdentity = $this->getMock('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface');

        $this->setExpectedException('InvalidArgumentException');
        SecurityIdentity::fromAclIdentity($secIdentity, $this->con);
    }

    public function testFromAclIdentityWithUser()
    {
        $secIdentity = new UserSecurityIdentity('propel', 'Propel\PropelBundle\Tests\Fixtures\UserProxy');

        $identity = SecurityIdentity::fromAclIdentity($secIdentity, $this->con);

        $this->assertInstanceOf('Propel\PropelBundle\Model\Acl\SecurityIdentity', $identity);
        $this->assertEquals(true, $identity->getUsername());
        $this->assertEquals('Propel\PropelBundle\Tests\Fixtures\UserProxy-propel', $identity->getIdentifier());
        $this->assertGreaterThan(0, $identity->getId());

        $dbEntry = SecurityIdentityQuery::create()->findPk($identity->getId());
        $this->assertInstanceOf('Propel\PropelBundle\Model\Acl\SecurityIdentity', $dbEntry);
    }

    public function testFromAclIdentityWithRole()
    {
        $secIdentity = new RoleSecurityIdentity(new Role('ROLE_USER'));

        $identity = SecurityIdentity::fromAclIdentity($secIdentity, $this->con);

        $this->assertInstanceOf('Propel\PropelBundle\Model\Acl\SecurityIdentity', $identity);
        $this->assertEquals(false, $identity->getUsername());
        $this->assertEquals('ROLE_USER', $identity->getIdentifier());
        $this->assertGreaterThan(0, $identity->getId());

        $dbEntry = SecurityIdentityQuery::create()->findPk($identity->getId());
        $this->assertInstanceOf('Propel\PropelBundle\Model\Acl\SecurityIdentity', $dbEntry);
    }
}
