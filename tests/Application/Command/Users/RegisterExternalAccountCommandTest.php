<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2018 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <http://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace eTraxis\Application\Command\Users;

use eTraxis\Application\Dictionary\AccountProvider;
use eTraxis\Entity\User;
use eTraxis\TransactionalTestCase;

/**
 * @covers \eTraxis\Application\Command\Users\Handler\RegisterExternalAccountHandler::__invoke
 */
class RegisterExternalAccountCommandTest extends TransactionalTestCase
{
    /**
     * @var \eTraxis\Repository\Contracts\UserRepositoryInterface
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(User::class);
    }

    public function testNewUser()
    {
        /** @var User $user */
        $user = $this->repository->findOneByUsername('anna@example.com');
        self::assertNull($user);

        $command = new RegisterExternalAccountCommand([
            'provider' => AccountProvider::LDAP,
            'uid'      => 'ldap-a56eb4e9',
            'email'    => 'anna@example.com',
            'fullname' => 'Anna Rodygina',
        ]);

        $result = $this->commandBus->handle($command);

        /** @var User $user */
        $user = $this->repository->findOneByUsername('anna@example.com');
        self::assertInstanceOf(User::class, $user);
        self::assertSame($result, $user);

        self::assertSame(AccountProvider::LDAP, $user->account->provider);
        self::assertSame('ldap-a56eb4e9', $user->account->uid);
        self::assertSame('anna@example.com', $user->email);
        self::assertSame('Anna Rodygina', $user->fullname);
        self::assertSame('en', $user->locale);
        self::assertSame('azure', $user->theme);
    }

    public function testExistingUserByUid()
    {
        /** @var User $user */
        $user = $this->repository->findOneByUsername('einstein@ldap.forumsys.com');
        self::assertNotNull($user);

        self::assertSame(AccountProvider::LDAP, $user->account->provider);
        self::assertSame('ldap-9fc3012e', $user->account->uid);
        self::assertSame('einstein@ldap.forumsys.com', $user->email);
        self::assertSame('Albert Einstein', $user->fullname);

        $command = new RegisterExternalAccountCommand([
            'provider' => AccountProvider::LDAP,
            'uid'      => 'ldap-9fc3012e',
            'email'    => 'anna@example.com',
            'fullname' => 'Anna Rodygina',
        ]);

        $result = $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($user);

        self::assertSame($result, $user);

        self::assertSame(AccountProvider::LDAP, $user->account->provider);
        self::assertSame('ldap-9fc3012e', $user->account->uid);
        self::assertSame('anna@example.com', $user->email);
        self::assertSame('Anna Rodygina', $user->fullname);
    }

    public function testExistingUserByEmail()
    {
        /** @var User $user */
        $user = $this->repository->findOneByUsername('artem@example.com');
        self::assertNotNull($user);

        self::assertSame(AccountProvider::ETRAXIS, $user->account->provider);
        self::assertNotSame('ldap-a56eb4e9', $user->account->uid);
        self::assertSame('artem@example.com', $user->email);
        self::assertSame('Artem Rodygin', $user->fullname);

        $command = new RegisterExternalAccountCommand([
            'provider' => AccountProvider::LDAP,
            'uid'      => 'ldap-a56eb4e9',
            'email'    => 'artem@example.com',
            'fullname' => 'Tomas Rodriges',
        ]);

        $result = $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($user);

        self::assertSame($result, $user);

        self::assertSame(AccountProvider::LDAP, $user->account->provider);
        self::assertSame('ldap-a56eb4e9', $user->account->uid);
        self::assertSame('artem@example.com', $user->email);
        self::assertSame('Tomas Rodriges', $user->fullname);
    }
}
