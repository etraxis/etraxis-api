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

use eTraxis\Entity\User;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @covers \eTraxis\Application\Command\Users\Handler\UpdateUserHandler::__invoke
 */
class UpdateUserCommandTest extends TransactionalTestCase
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
        $user = $this->repository->loadUserByUsername('nhills@example.com');

        static::assertSame('Nikko Hills', $user->fullname);
        static::assertNotEmpty($user->description);
        static::assertFalse($user->isAdmin);
        static::assertTrue($user->isEnabled());
        static::assertSame('en_US', $user->locale);
        static::assertSame('azure', $user->theme);
        static::assertSame('UTC', $user->timezone);

        $command = new UpdateUserCommand([
            'user'     => $user->id,
            'email'    => 'chaim.willms@example.com',
            'fullname' => 'Chaim Willms',
            'admin'    => true,
            'disabled' => true,
            'locale'   => 'ru',
            'theme'    => 'emerald',
            'timezone' => 'Asia/Vladivostok',
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($user);

        static::assertSame('chaim.willms@example.com', $user->email);
        static::assertSame('Chaim Willms', $user->fullname);
        static::assertEmpty($user->description);
        static::assertTrue($user->isAdmin);
        static::assertFalse($user->isEnabled());
        static::assertSame('ru', $user->locale);
        static::assertSame('emerald', $user->theme);
        static::assertSame('Asia/Vladivostok', $user->timezone);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var User $user */
        $user = $this->repository->loadUserByUsername('nhills@example.com');

        $command = new UpdateUserCommand([
            'user'        => $user->id,
            'email'       => $user->email,
            'fullname'    => $user->fullname,
            'description' => $user->description,
            'admin'       => $user->isAdmin,
            'disabled'    => !$user->isEnabled(),
            'locale'      => $user->locale,
            'theme'       => $user->theme,
            'timezone'    => $user->timezone,
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownUser()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        $command = new UpdateUserCommand([
            'user'     => self::UNKNOWN_ENTITY_ID,
            'email'    => 'chaim.willms@example.com',
            'fullname' => 'Chaim Willms',
            'admin'    => true,
            'disabled' => true,
            'locale'   => 'ru',
            'theme'    => 'emerald',
            'timezone' => 'Asia/Vladivostok',
        ]);

        $this->commandBus->handle($command);
    }

    public function testUsernameConflict()
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Account with specified email already exists.');

        $this->loginAs('admin@example.com');

        /** @var User $user */
        $user = $this->repository->loadUserByUsername('nhills@example.com');

        $command = new UpdateUserCommand([
            'user'        => $user->id,
            'email'       => 'vparker@example.com',
            'fullname'    => $user->fullname,
            'description' => $user->description,
            'admin'       => $user->isAdmin,
            'disabled'    => !$user->isEnabled(),
            'locale'      => $user->locale,
            'theme'       => $user->theme,
            'timezone'    => $user->timezone,
        ]);

        $this->commandBus->handle($command);
    }
}
