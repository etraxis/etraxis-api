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

/**
 * @covers \eTraxis\Application\Command\Users\Handler\UpdateSettingsHandler::__invoke
 */
class UpdateSettingsCommandTest extends TransactionalTestCase
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

    public function testSuccess()
    {
        $this->loginAs('artem@example.com');

        /** @var User $user */
        $user = $this->repository->findOneByUsername('artem@example.com');

        self::assertSame('en', $user->locale);
        self::assertSame('UTC', $user->timezone);

        $command = new UpdateSettingsCommand([
            'locale'   => 'ru',
            'timezone' => 'Pacific/Auckland',
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($user);

        self::assertSame('ru', $user->locale);
        self::assertSame('Pacific/Auckland', $user->timezone);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $command = new UpdateSettingsCommand([
            'locale'   => 'ru',
            'timezone' => 'Pacific/Auckland',
        ]);

        $this->commandBus->handle($command);
    }
}
