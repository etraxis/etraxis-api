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
 * @covers \eTraxis\Application\Command\Users\Handler\UpdateSettingsHandler::__invoke
 */
class UpdateSettingsCommandTest extends TransactionalTestCase
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
        $this->loginAs('artem@example.com');

        /** @var User $user */
        $user = $this->repository->loadUserByUsername('artem@example.com');

        static::assertSame('en_US', $user->locale);
        static::assertSame('azure', $user->theme);
        static::assertTrue($user->isLightMode);
        static::assertSame('UTC', $user->timezone);

        $command = new UpdateSettingsCommand([
            'locale'     => 'ru',
            'theme'      => 'emerald',
            'light_mode' => false,
            'timezone'   => 'Pacific/Auckland',
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($user);

        static::assertSame('ru', $user->locale);
        static::assertSame('emerald', $user->theme);
        static::assertFalse($user->isLightMode);
        static::assertSame('Pacific/Auckland', $user->timezone);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $command = new UpdateSettingsCommand([
            'locale'     => 'ru',
            'theme'      => 'emerald',
            'light_mode' => false,
            'timezone'   => 'Pacific/Auckland',
        ]);

        $this->commandBus->handle($command);
    }
}
