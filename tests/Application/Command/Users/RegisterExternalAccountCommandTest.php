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

namespace eTraxis\Application\Command\Users;

use eTraxis\Application\Dictionary\AccountProvider;
use eTraxis\Entity\User;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use eTraxis\TransactionalTestCase;

/**
 * @covers \eTraxis\Application\Command\Users\Handler\RegisterExternalAccountHandler::__invoke
 */
class RegisterExternalAccountCommandTest extends TransactionalTestCase
{
    private UserRepositoryInterface $repository;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(User::class);
    }

    public function testNewUser()
    {
        /** @var User $user */
        $user = $this->repository->loadUserByUsername('anna@example.com');
        static::assertNull($user);

        $command = new RegisterExternalAccountCommand([
            'provider' => AccountProvider::LDAP,
            'uid'      => 'ldap-a56eb4e9',
            'email'    => 'anna@example.com',
            'fullname' => 'Anna Rodygina',
        ]);

        $result = $this->commandBus->handle($command);

        /** @var User $user */
        $user = $this->repository->loadUserByUsername('anna@example.com');
        static::assertInstanceOf(User::class, $user);
        static::assertSame($result, $user);

        static::assertSame(AccountProvider::LDAP, $user->account->provider);
        static::assertSame('ldap-a56eb4e9', $user->account->uid);
        static::assertSame('anna@example.com', $user->email);
        static::assertSame('Anna Rodygina', $user->fullname);
        static::assertSame('en_US', $user->locale);
        static::assertSame('azure', $user->theme);
    }

    public function testExistingUserByUid()
    {
        /** @var User $user */
        $user = $this->repository->loadUserByUsername('einstein@ldap.forumsys.com');
        static::assertNotNull($user);

        static::assertSame(AccountProvider::LDAP, $user->account->provider);
        static::assertSame('ldap-9fc3012e', $user->account->uid);
        static::assertSame('einstein@ldap.forumsys.com', $user->email);
        static::assertSame('Albert Einstein', $user->fullname);

        $command = new RegisterExternalAccountCommand([
            'provider' => AccountProvider::LDAP,
            'uid'      => 'ldap-9fc3012e',
            'email'    => 'anna@example.com',
            'fullname' => 'Anna Rodygina',
        ]);

        $result = $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($user);

        static::assertSame($result, $user);

        static::assertSame(AccountProvider::LDAP, $user->account->provider);
        static::assertSame('ldap-9fc3012e', $user->account->uid);
        static::assertSame('anna@example.com', $user->email);
        static::assertSame('Anna Rodygina', $user->fullname);
    }

    public function testExistingUserByEmail()
    {
        /** @var User $user */
        $user = $this->repository->loadUserByUsername('artem@example.com');
        static::assertNotNull($user);

        static::assertSame(AccountProvider::ETRAXIS, $user->account->provider);
        static::assertNotSame('ldap-a56eb4e9', $user->account->uid);
        static::assertSame('artem@example.com', $user->email);
        static::assertSame('Artem Rodygin', $user->fullname);

        $command = new RegisterExternalAccountCommand([
            'provider' => AccountProvider::LDAP,
            'uid'      => 'ldap-a56eb4e9',
            'email'    => 'artem@example.com',
            'fullname' => 'Tomas Rodriges',
        ]);

        $result = $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($user);

        static::assertSame($result, $user);

        static::assertSame(AccountProvider::LDAP, $user->account->provider);
        static::assertSame('ldap-a56eb4e9', $user->account->uid);
        static::assertSame('artem@example.com', $user->email);
        static::assertSame('Tomas Rodriges', $user->fullname);
    }
}
