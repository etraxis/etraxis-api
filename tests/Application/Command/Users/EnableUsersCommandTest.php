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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @covers \eTraxis\Application\Command\Users\Handler\EnableUsersHandler::__invoke
 */
class EnableUsersCommandTest extends TransactionalTestCase
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

        /** @var User $nhills */
        /** @var User $tberge */
        $nhills = $this->repository->loadUserByUsername('nhills@example.com');
        $tberge = $this->repository->loadUserByUsername('tberge@example.com');

        self::assertTrue($nhills->isEnabled());
        self::assertFalse($tberge->isEnabled());

        $command = new EnableUsersCommand([
            'users' => [
                $nhills->id,
                $tberge->id,
            ],
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($nhills);
        $this->doctrine->getManager()->refresh($tberge);

        self::assertTrue($nhills->isEnabled());
        self::assertTrue($tberge->isEnabled());
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var User $user */
        $user = $this->repository->loadUserByUsername('tberge@example.com');

        $command = new EnableUsersCommand([
            'users' => [
                $user->id,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testNotFound()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        $command = new EnableUsersCommand([
            'users' => [
                self::UNKNOWN_ENTITY_ID,
            ],
        ]);

        $this->commandBus->handle($command);
    }
}
