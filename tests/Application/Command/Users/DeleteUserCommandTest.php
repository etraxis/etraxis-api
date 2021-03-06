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

/**
 * @covers \eTraxis\Application\Command\Users\Handler\DeleteUserHandler::__invoke
 */
class DeleteUserCommandTest extends TransactionalTestCase
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
        $user = $this->repository->loadUserByUsername('hstroman@example.com');
        static::assertNotNull($user);

        $command = new DeleteUserCommand([
            'user' => $user->id,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->clear();

        $user = $this->repository->loadUserByUsername('hstroman@example.com');
        static::assertNull($user);
    }

    public function testUnknown()
    {
        $this->loginAs('admin@example.com');

        $command = new DeleteUserCommand([
            'user' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandBus->handle($command);

        static::assertTrue(true);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var User $user */
        $user = $this->repository->loadUserByUsername('hstroman@example.com');

        $command = new DeleteUserCommand([
            'user' => $user->id,
        ]);

        $this->commandBus->handle($command);
    }

    public function testForbidden()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var User $user */
        $user = $this->repository->loadUserByUsername('admin@example.com');

        $command = new DeleteUserCommand([
            'user' => $user->id,
        ]);

        $this->commandBus->handle($command);
    }
}
