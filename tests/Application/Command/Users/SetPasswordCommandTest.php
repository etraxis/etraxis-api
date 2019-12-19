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
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Encoder\BasePasswordEncoder;

/**
 * @covers \eTraxis\Application\Command\Users\Handler\SetPasswordHandler::__invoke
 */
class SetPasswordCommandTest extends TransactionalTestCase
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

    public function testSuccessAsAdmin()
    {
        $this->loginAs('admin@example.com');

        /** @var \Symfony\Component\Security\Core\Encoder\UserPasswordEncoder $encoder */
        $encoder = $this->client->getContainer()->get('security.password_encoder');

        /** @var User $user */
        $user = $this->repository->findOneByUsername('artem@example.com');

        self::assertTrue($encoder->isPasswordValid($user, 'secret'));

        $command = new SetPasswordCommand([
            'user'     => $user->id,
            'password' => 'newone',
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($user);

        self::assertFalse($encoder->isPasswordValid($user, 'secret'));
        self::assertTrue($encoder->isPasswordValid($user, 'newone'));
    }

    public function testSuccessAsOwner()
    {
        $this->loginAs('artem@example.com');

        /** @var \Symfony\Component\Security\Core\Encoder\UserPasswordEncoder $encoder */
        $encoder = $this->client->getContainer()->get('security.password_encoder');

        /** @var User $user */
        $user = $this->repository->findOneByUsername('artem@example.com');

        self::assertTrue($encoder->isPasswordValid($user, 'secret'));

        $command = new SetPasswordCommand([
            'user'     => $user->id,
            'password' => 'newone',
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($user);

        self::assertFalse($encoder->isPasswordValid($user, 'secret'));
        self::assertTrue($encoder->isPasswordValid($user, 'newone'));
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var User $user */
        $user = $this->repository->findOneByUsername('admin@example.com');

        $command = new SetPasswordCommand([
            'user'     => $user->id,
            'password' => 'secret',
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownUser()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        $command = new SetPasswordCommand([
            'user'     => self::UNKNOWN_ENTITY_ID,
            'password' => 'secret',
        ]);

        $this->commandBus->handle($command);
    }

    public function testExternalUser()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var User $user */
        $user = $this->repository->findOneByUsername('einstein@ldap.forumsys.com');

        $command = new SetPasswordCommand([
            'user'     => $user->id,
            'password' => 'secret',
        ]);

        $this->commandBus->handle($command);
    }

    public function testInvalidPassword()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid password.');

        $this->loginAs('admin@example.com');

        /** @var User $user */
        $user = $this->repository->findOneByUsername('artem@example.com');

        $command = new SetPasswordCommand([
            'user'     => $user->id,
            'password' => str_repeat('*', BasePasswordEncoder::MAX_PASSWORD_LENGTH + 1),
        ]);

        $this->commandBus->handle($command);
    }
}
