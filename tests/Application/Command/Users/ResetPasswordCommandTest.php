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
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Encoder\BasePasswordEncoder;

/**
 * @covers \eTraxis\Application\Command\Users\Handler\ResetPasswordHandler::__invoke
 */
class ResetPasswordCommandTest extends TransactionalTestCase
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
        /** @var \Symfony\Component\Security\Core\Encoder\UserPasswordEncoder $encoder */
        $encoder = $this->client->getContainer()->get('security.password_encoder');

        /** @var User $user */
        $user = $this->repository->loadUserByUsername('artem@example.com');

        $token = $user->generateResetToken(new \DateInterval('PT1M'));

        $this->doctrine->getManager()->persist($user);
        $this->doctrine->getManager()->flush();

        self::assertTrue($encoder->isPasswordValid($user, 'secret'));
        self::assertTrue($user->isResetTokenValid($token));

        $command = new ResetPasswordCommand([
            'token'    => $token,
            'password' => 'newone',
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($user);

        self::assertFalse($encoder->isPasswordValid($user, 'secret'));
        self::assertTrue($encoder->isPasswordValid($user, 'newone'));
        self::assertFalse($user->isResetTokenValid($token));
    }

    public function testUnknownToken()
    {
        $this->expectException(NotFoundHttpException::class);

        $command = new ResetPasswordCommand([
            'token'    => Uuid::uuid4()->getHex()->toString(),
            'password' => 'secret',
        ]);

        $this->commandBus->handle($command);
    }

    public function testExpiredToken()
    {
        $this->expectException(NotFoundHttpException::class);

        /** @var User $user */
        $user = $this->repository->loadUserByUsername('artem@example.com');

        $token = $user->generateResetToken(new \DateInterval('PT0M'));

        $this->doctrine->getManager()->persist($user);
        $this->doctrine->getManager()->flush();

        self::assertFalse($user->isResetTokenValid($token));

        $command = new ResetPasswordCommand([
            'token'    => $token,
            'password' => 'secret',
        ]);

        $this->commandBus->handle($command);
    }

    public function testInvalidPassword()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid password.');

        /** @var User $user */
        $user = $this->repository->loadUserByUsername('artem@example.com');

        $token = $user->generateResetToken(new \DateInterval('PT1M'));

        $this->doctrine->getManager()->persist($user);
        $this->doctrine->getManager()->flush();

        $command = new ResetPasswordCommand([
            'token'    => $token,
            'password' => str_repeat('*', BasePasswordEncoder::MAX_PASSWORD_LENGTH + 1),
        ]);

        $this->commandBus->handle($command);
    }
}
