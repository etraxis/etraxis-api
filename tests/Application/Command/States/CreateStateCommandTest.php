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

namespace eTraxis\Application\Command\States;

use eTraxis\Application\Dictionary\StateResponsible;
use eTraxis\Application\Dictionary\StateType;
use eTraxis\Entity\State;
use eTraxis\Entity\Template;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @covers \eTraxis\Application\Command\States\Handler\CreateStateHandler::__invoke
 */
class CreateStateCommandTest extends TransactionalTestCase
{
    /**
     * @var \eTraxis\Repository\Contracts\StateRepositoryInterface
     */
    private $repository;

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
        self::assertNull($state);

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
        self::assertInstanceOf(State::class, $state);
        self::assertSame($result, $state);

        self::assertSame($template, $state->template);
        self::assertSame('Started', $state->name);
        self::assertSame(StateType::INTERMEDIATE, $state->type);
        self::assertSame(StateResponsible::KEEP, $state->responsible);
        self::assertSame($nextState, $state->nextState);
    }

    public function testInitial()
    {
        $this->loginAs('admin@example.com');

        /** @var Template $template */
        [/* skipping */, $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        /** @var State $initial */
        [/* skipping */, $initial] = $this->repository->findBy(['name' => 'New'], ['id' => 'ASC']);
        self::assertSame(StateType::INITIAL, $initial->type);

        /** @var State $state */
        $state = $this->repository->findOneBy(['name' => 'Created']);
        self::assertNull($state);

        $command = new CreateStateCommand([
            'template'    => $template->id,
            'name'        => 'Created',
            'type'        => StateType::INITIAL,
            'responsible' => StateResponsible::KEEP,
        ]);

        $result = $this->commandBus->handle($command);

        /** @var State $state */
        $state = $this->repository->findOneBy(['name' => 'Created']);
        self::assertInstanceOf(State::class, $state);
        self::assertSame($result, $state);

        self::assertSame($template, $state->template);
        self::assertSame('Created', $state->name);
        self::assertSame(StateType::INITIAL, $state->type);
        self::assertSame(StateResponsible::KEEP, $state->responsible);

        $this->doctrine->getManager()->refresh($initial);

        self::assertSame(StateType::INTERMEDIATE, $initial->type);
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
