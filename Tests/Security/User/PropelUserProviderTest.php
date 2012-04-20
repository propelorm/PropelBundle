<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Tests\Security\User;

use Propel\PropelBundle\Tests\Fixtures\Model\User;
use Propel\PropelBundle\Tests\TestCase;
use Symfony\Bridge\Propel1\Security\User\PropelUserProvider;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class PropelUserProviderTest extends TestCase
{
    public function setUp()
    {
        $this->loadPropelQuickBuilder();

        $schema = <<<SCHEMA
<database name="users" defaultIdMethod="native" namespace="Propel\\PropelBundle\\Tests\\Fixtures\\Model">
    <table name="user">
        <column name="id" type="integer" required="true" primaryKey="true" autoIncrement="true" />
        <column name="username" type="varchar" size="255" primaryString="true" />
        <column name="algorithm" type="varchar" size="50" />
        <column name="salt" type="varchar" size="255" />
        <column name="password" type="varchar" size="255" />
        <column name="expires_at" type="timestamp" />
        <column name="roles" type="array" />
    </table>
</database>
SCHEMA;

        $builder = new \PropelQuickBuilder();
        $builder->setSchema($schema);
        $builder->setClassTargets(array('tablemap', 'peer', 'object', 'query', 'peerstub', 'querystub'));
        $builder->build();
    }

    public function testRefreshUserGetsUserByPrimaryKey()
    {
        $user1 = new User();
        $user1->setUsername('user1');
        $user1->save();

        $user2 = new User();
        $user2->setUsername('user2');
        $user2->save();

        $provider = new PropelUserProvider('Propel\PropelBundle\Tests\Fixtures\Model\User', 'username');

        // try to change the user identity
        $user1->setUsername('user2');

        $resultUser = $provider->refreshUser($user1);
        $this->assertSame($user1, $resultUser);
    }
}
