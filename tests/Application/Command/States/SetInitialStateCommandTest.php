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

namespace eTraxis\Application\Command\States;

use eTraxis\Application\Dictionary\StateType;
use eTraxis\Entity\State;
use eTraxis\Repository\Contracts\StateRepositoryInterface;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @covers \eTraxis\Application\Command\States\Handler\SetInitialStateHandler::__invoke
 */
class SetInitialStateCommandTest extends TransactionalTestCase
{
    private StateRepositoryInterface $repository;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(State::class);
    }

    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var State $initial */
        /** @var State $state */
        [/* skipping */, $initial] = $this->repository->findBy(['name' => 'New'], ['id' => 'ASC']);
        [/* skipping */, $state]   = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        static::assertSame(StateType::INITIAL, $initial->type);
        static::assertNotSame(StateType::INITIAL, $state->type);

        $command = new SetInitialStateCommand([
            'state' => $state->id,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($initial);
        $this->doctrine->getManager()->refresh($state);

        static::assertNotSame(StateType::INITIAL, $initial->type);
        static::assertSame(StateType::INITIAL, $state->type);
    }

    public function testInitialState()
    {
        $this->loginAs('admin@example.com');

        /** @var State $state */
        [/* skipping */, $state] = $this->repository->findBy(['name' => 'New'], ['id' => 'ASC']);

        static::assertSame(StateType::INITIAL, $state->type);

        $command = new SetInitialStateCommand([
            'state' => $state->id,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($state);

        static::assertSame(StateType::INITIAL, $state->type);
    }

    public function testUnknownState()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        $command = new SetInitialStateCommand([
            'state' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandBus->handle($command);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var State $state */
        [/* skipping */, $state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $command = new SetInitialStateCommand([
            'state' => $state->id,
        ]);

        $this->commandBus->handle($command);
    }
}
