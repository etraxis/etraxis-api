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
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Security\Core\Encoder\BasePasswordEncoder;

/**
 * @covers \eTraxis\Application\Command\Users\Handler\CreateUserHandler::__invoke
 */
class CreateUserCommandTest extends TransactionalTestCase
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

    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var User $user */
        $user = $this->repository->loadUserByUsername('anna@example.com');
        static::assertNull($user);

        $command = new CreateUserCommand([
            'email'       => 'anna@example.com',
            'password'    => 'secret',
            'fullname'    => 'Anna Rodygina',
            'description' => 'Very lovely Daughter',
            'admin'       => true,
            'disabled'    => false,
            'locale'      => 'ru',
            'theme'       => 'emerald',
            'timezone'    => 'Pacific/Auckland',
        ]);

        $result = $this->commandBus->handle($command);

        /** @var User $user */
        $user = $this->repository->loadUserByUsername('anna@example.com');
        static::assertInstanceOf(User::class, $user);
        static::assertSame($result, $user);

        static::assertSame(AccountProvider::ETRAXIS, $user->account->provider);
        static::assertRegExp('/^([0-9a-f]{32}$)/', $user->account->uid);
        static::assertSame('anna@example.com', $user->email);
        static::assertSame('Anna Rodygina', $user->fullname);
        static::assertSame('Very lovely Daughter', $user->description);
        static::assertTrue($user->isEnabled());
        static::assertTrue($user->isAdmin);
        static::assertSame('ru', $user->locale);
        static::assertSame('emerald', $user->theme);
        static::assertSame('Pacific/Auckland', $user->timezone);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        $command = new CreateUserCommand([
            'email'       => 'anna@example.com',
            'password'    => 'secret',
            'fullname'    => 'Anna Rodygina',
            'description' => 'Very lovely Daughter',
            'admin'       => true,
            'disabled'    => false,
            'locale'      => 'ru',
            'theme'       => 'emerald',
            'timezone'    => 'Pacific/Auckland',
        ]);

        $this->commandBus->handle($command);
    }

    public function testInvalidPassword()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid password.');

        $this->loginAs('admin@example.com');

        $command = new CreateUserCommand([
            'email'       => 'anna@example.com',
            'password'    => str_repeat('*', BasePasswordEncoder::MAX_PASSWORD_LENGTH + 1),
            'fullname'    => 'Anna Rodygina',
            'description' => 'Very lovely Daughter',
            'admin'       => true,
            'disabled'    => false,
            'locale'      => 'ru',
            'theme'       => 'emerald',
            'timezone'    => 'Pacific/Auckland',
        ]);

        $this->commandBus->handle($command);
    }

    public function testUsernameConflict()
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Account with specified email already exists.');

        $this->loginAs('admin@example.com');

        $command = new CreateUserCommand([
            'email'       => 'artem@example.com',
            'password'    => 'secret',
            'fullname'    => 'Anna Rodygina',
            'description' => 'Very lovely Daughter',
            'admin'       => true,
            'disabled'    => false,
            'locale'      => 'ru',
            'theme'       => 'emerald',
            'timezone'    => 'Pacific/Auckland',
        ]);

        $this->commandBus->handle($command);
    }
}
