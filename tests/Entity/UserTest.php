<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2018 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <https://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace eTraxis\Entity;

use eTraxis\Application\Dictionary\AccountProvider;
use eTraxis\ReflectionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \eTraxis\Entity\User
 */
class UserTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @covers ::__construct
     */
    public function testConstructor()
    {
        $user = new User();

        static::assertSame(['ROLE_USER'], $user->getRoles());
        static::assertSame(AccountProvider::ETRAXIS, $user->account->provider);
        static::assertRegExp('/^([[:xdigit:]]{32})$/is', $user->account->uid);
    }

    /**
     * @covers ::getUsername
     */
    public function testUsername()
    {
        $user = new User();
        static::assertNotSame('anna@example.com', $user->getUsername());

        $user->email = 'anna@example.com';
        static::assertSame('anna@example.com', $user->getUsername());
    }

    /**
     * @covers ::getPassword
     */
    public function testPassword()
    {
        $user = new User();
        static::assertNotSame('secret', $user->getPassword());

        $user->password = 'secret';
        static::assertSame('secret', $user->getPassword());
    }

    /**
     * @covers ::getRoles
     */
    public function testRoles()
    {
        $user = new User();
        static::assertSame(['ROLE_USER'], $user->getRoles());

        $user->isAdmin = true;
        static::assertSame(['ROLE_ADMIN'], $user->getRoles());

        $user->isAdmin = false;
        static::assertSame(['ROLE_USER'], $user->getRoles());
    }

    /**
     * @covers ::isAccountExternal
     */
    public function testIsAccountExternal()
    {
        $user = new User();
        static::assertFalse($user->isAccountExternal());

        $user->account->provider = AccountProvider::LDAP;
        static::assertTrue($user->isAccountExternal());

        $user->account->provider = AccountProvider::ETRAXIS;
        static::assertFalse($user->isAccountExternal());
    }

    /**
     * @covers ::getters
     * @covers ::setters
     */
    public function testIsAdmin()
    {
        $user = new User();
        static::assertFalse($user->isAdmin);

        $user->isAdmin = true;
        static::assertTrue($user->isAdmin);

        $user->isAdmin = false;
        static::assertFalse($user->isAdmin);
    }

    /**
     * @covers ::getters
     * @covers ::setters
     */
    public function testLocale()
    {
        $user = new User();
        static::assertSame('en_US', $user->locale);

        $user->locale = 'ru';
        static::assertSame('ru', $user->locale);

        $user->locale = 'xx';
        static::assertSame('ru', $user->locale);
    }

    /**
     * @covers ::getters
     * @covers ::setters
     */
    public function testTheme()
    {
        $user = new User();
        static::assertSame('azure', $user->theme);

        $user->theme = 'emerald';
        static::assertSame('emerald', $user->theme);

        $user->theme = 'unknown';
        static::assertSame('emerald', $user->theme);
    }

    /**
     * @covers ::getters
     * @covers ::setters
     */
    public function testIsLightMode()
    {
        $user = new User();
        static::assertTrue($user->isLightMode);

        $user->isLightMode = false;
        static::assertFalse($user->isLightMode);

        $user->isLightMode = true;
        static::assertTrue($user->isLightMode);
    }

    /**
     * @covers ::getters
     * @covers ::setters
     */
    public function testTimezone()
    {
        $user = new User();
        static::assertSame('UTC', $user->timezone);

        $user->timezone = 'Pacific/Auckland';
        static::assertSame('Pacific/Auckland', $user->timezone);

        $user->timezone = 'Unknown';
        static::assertSame('Pacific/Auckland', $user->timezone);
    }

    /**
     * @covers ::getters
     * @covers ::setters
     */
    public function testGroups()
    {
        $user = new User();
        static::assertSame([], $user->groups);

        /** @var \Doctrine\Common\Collections\Collection $groups */
        $groups = $this->getProperty($user, 'groupsCollection');
        $groups->add('Group A');
        $groups->add('Group B');

        static::assertSame(['Group A', 'Group B'], $user->groups);
    }

    /**
     * @covers ::canAccountBeLocked
     */
    public function testCanAccountBeLocked()
    {
        $user = new User();
        static::assertTrue($this->callMethod($user, 'canAccountBeLocked'));

        $user->account->provider = AccountProvider::LDAP;
        static::assertFalse($this->callMethod($user, 'canAccountBeLocked'));

        $user->account->provider = AccountProvider::ETRAXIS;
        static::assertTrue($this->callMethod($user, 'canAccountBeLocked'));
    }
}
