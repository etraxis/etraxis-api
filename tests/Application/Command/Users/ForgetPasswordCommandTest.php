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

use eTraxis\Entity\User;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use eTraxis\TransactionalTestCase;

/**
 * @covers \eTraxis\Application\Command\Users\Handler\ForgetPasswordHandler::__invoke
 */
class ForgetPasswordCommandTest extends TransactionalTestCase
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
        $command = new ForgetPasswordCommand([
            'email' => 'artem@example.com',
        ]);

        $token = $this->commandBus->handle($command);
        self::assertRegExp('/^([0-9a-f]{32}$)/', $token);

        /** @var User $user */
        $user = $this->repository->loadUserByUsername('artem@example.com');
        self::assertTrue($user->isResetTokenValid($token));
    }

    public function testExternal()
    {
        $user = $this->repository->loadUserByUsername('einstein@ldap.forumsys.com');
        self::assertNotNull($user);

        $command = new ForgetPasswordCommand([
            'email' => 'einstein@ldap.forumsys.com',
        ]);

        $token = $this->commandBus->handle($command);
        self::assertNull($token);

        $users = $this->repository->findBy(['resetToken' => null]);
        self::assertCount(count($this->repository->findAll()), $users);
    }

    public function testUnknown()
    {
        $user = $this->repository->loadUserByUsername('404@example.com');
        self::assertNull($user);

        $command = new ForgetPasswordCommand([
            'email' => '404@example.com',
        ]);

        $token = $this->commandBus->handle($command);
        self::assertNull($token);

        $users = $this->repository->findBy(['resetToken' => null]);
        self::assertCount(count($this->repository->findAll()), $users);
    }
}
