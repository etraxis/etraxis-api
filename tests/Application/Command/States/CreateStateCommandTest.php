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

use eTraxis\Application\Dictionary\StateResponsible;
use eTraxis\Application\Dictionary\StateType;
use eTraxis\Entity\State;
use eTraxis\Entity\Template;
use eTraxis\Repository\Contracts\StateRepositoryInterface;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @covers \eTraxis\Application\Command\States\Handler\CreateStateHandler::__invoke
 */
class CreateStateCommandTest extends TransactionalTestCase
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

        /** @var Template $template */
        [/* skipping */, $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        /** @var State $nextState */
        [/* skipping */, $nextState] = $this->repository->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        /** @var State $state */
        $state = $this->repository->findOneBy(['name' => 'Started']);
        static::assertNull($state);

        $command = new CreateStateCommand([
            'template'    => $template->id,
            'name'        => 'Started',
            'type'        => StateType::INTERMEDIATE,
            'responsible' => StateResponsible::KEEP,
            'next'        => $nextState->id,
        ]);

        $result = $this->commandBus->handle($command);

        /** @var State $state */
        $state = $this->repository->findOneBy(['name' => 'Started']);
        static::assertInstanceOf(State::class, $state);
        static::assertSame($result, $state);

        static::assertSame($template, $state->template);
        static::assertSame('Started', $state->name);
        static::assertSame(StateType::INTERMEDIATE, $state->type);
        static::assertSame(StateResponsible::KEEP, $state->responsible);
        static::assertSame($nextState, $state->nextState);
    }

    public function testInitial()
    {
        $this->loginAs('admin@example.com');

        /** @var Template $template */
        [/* skipping */, $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        /** @var State $initial */
        [/* skipping */, $initial] = $this->repository->findBy(['name' => 'New'], ['id' => 'ASC']);
        static::assertSame(StateType::INITIAL, $initial->type);

        /** @var State $state */
        $state = $this->repository->findOneBy(['name' => 'Created']);
        static::assertNull($state);

        $command = new CreateStateCommand([
            'template'    => $template->id,
            'name'        => 'Created',
            'type'        => StateType::INITIAL,
            'responsible' => StateResponsible::KEEP,
        ]);

        $result = $this->commandBus->handle($command);

        /** @var State $state */
        $state = $this->repository->findOneBy(['name' => 'Created']);
        static::assertInstanceOf(State::class, $state);
        static::assertSame($result, $state);

        static::assertSame($template, $state->template);
        static::assertSame('Created', $state->name);
        static::assertSame(StateType::INITIAL, $state->type);
        static::assertSame(StateResponsible::KEEP, $state->responsible);

        $this->doctrine->getManager()->refresh($initial);

        static::assertSame(StateType::INTERMEDIATE, $initial->type);
    }

    public function testUnknownTemplate()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        $command = new CreateStateCommand([
            'template'    => self::UNKNOWN_ENTITY_ID,
            'name'        => 'Started',
            'type'        => StateType::INTERMEDIATE,
            'responsible' => StateResponsible::KEEP,
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownNextState()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown next state.');

        $this->loginAs('admin@example.com');

        /** @var Template $template */
        [/* skipping */, $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $command = new CreateStateCommand([
            'template'    => $template->id,
            'name'        => 'Started',
            'type'        => StateType::INTERMEDIATE,
            'responsible' => StateResponsible::KEEP,
            'next'        => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandBus->handle($command);
    }

    public function testWrongNextState()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown next state.');

        $this->loginAs('admin@example.com');

        /** @var Template $template */
        [/* skipping */, $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        /** @var State $nextState */
        [/* skipping */, $nextState] = $this->repository->findBy(['name' => 'Completed'], ['id' => 'DESC']);

        $command = new CreateStateCommand([
            'template'    => $template->id,
            'name'        => 'Started',
            'type'        => StateType::INTERMEDIATE,
            'responsible' => StateResponsible::KEEP,
            'next'        => $nextState->id,
        ]);

        $this->commandBus->handle($command);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var Template $template */
        [/* skipping */, $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $command = new CreateStateCommand([
            'template'    => $template->id,
            'name'        => 'Started',
            'type'        => StateType::INTERMEDIATE,
            'responsible' => StateResponsible::KEEP,
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnlockedTemplate()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $command = new CreateStateCommand([
            'template'    => $template->id,
            'name'        => 'Started',
            'type'        => StateType::INTERMEDIATE,
            'responsible' => StateResponsible::KEEP,
        ]);

        $this->commandBus->handle($command);
    }

    public function testNameConflict()
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('State with specified name already exists.');

        $this->loginAs('admin@example.com');

        /** @var Template $template */
        [/* skipping */, $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $command = new CreateStateCommand([
            'template'    => $template->id,
            'name'        => 'Completed',
            'type'        => StateType::INTERMEDIATE,
            'responsible' => StateResponsible::KEEP,
        ]);

        $this->commandBus->handle($command);
    }
}
