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

/**
 * @covers \eTraxis\Application\Command\Users\Handler\UpdateProfileHandler::__invoke
 */
class UpdateProfileCommandTest extends TransactionalTestCase
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
        $this->loginAs('nhills@example.com');

        /** @var User $user */
        $user = $this->repository->loadUserByUsername('nhills@example.com');

        static::assertSame('nhills@example.com', $user->email);
        static::assertSame('Nikko Hills', $user->fullname);

        $command = new UpdateProfileCommand([
            'email'    => 'chaim.willms@example.com',
            'fullname' => 'Chaim Willms',
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($user);

        static::assertSame('chaim.willms@example.com', $user->email);
        static::assertSame('Chaim Willms', $user->fullname);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $command = new UpdateProfileCommand([
            'email'    => 'chaim.willms@example.com',
            'fullname' => 'Chaim Willms',
        ]);

        $this->commandBus->handle($command);
    }

    public function testExternalAccount()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('einstein@ldap.forumsys.com');

        $command = new UpdateProfileCommand([
            'email'    => 'chaim.willms@example.com',
            'fullname' => 'Chaim Willms',
        ]);

        $this->commandBus->handle($command);
    }

    public function testUsernameConflict()
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Account with specified email already exists.');

        $this->loginAs('nhills@example.com');

        $command = new UpdateProfileCommand([
            'email'    => 'vparker@example.com',
            'fullname' => 'Chaim Willms',
        ]);

        $this->commandBus->handle($command);
    }
}
