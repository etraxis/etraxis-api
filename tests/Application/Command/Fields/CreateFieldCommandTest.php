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

namespace eTraxis\Application\Command\Fields;

use eTraxis\Application\Dictionary\FieldType;
use eTraxis\Entity\Field;
use eTraxis\Entity\State;
use eTraxis\Repository\Contracts\FieldRepositoryInterface;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @covers \eTraxis\Application\Command\Fields\Handler\AbstractCreateFieldHandler::create
 */
class CreateFieldCommandTest extends TransactionalTestCase
{
    private FieldRepositoryInterface $repository;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Field::class);
    }

    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var State $state */
        [/* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Duplicated'], ['id' => 'ASC']);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['name' => 'Task ID', 'removedAt' => null]);
        static::assertNull($field);

        $command = new CreateIssueFieldCommand([
            'state'       => $state->id,
            'name'        => 'Task ID',
            'description' => 'ID of the duplicating task.',
            'required'    => true,
        ]);

        $result = $this->commandBus->handle($command);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['name' => 'Task ID', 'removedAt' => null]);
        static::assertInstanceOf(Field::class, $field);
        static::assertSame($result, $field);

        static::assertSame(FieldType::ISSUE, $field->type);
        static::assertSame($state, $field->state);
        static::assertSame('Task ID', $field->name);
        static::assertSame('ID of the duplicating task.', $field->description);
        static::assertSame(2, $field->position);
        static::assertTrue($field->isRequired);
    }

    public function testUnknownState()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        $command = new CreateIssueFieldCommand([
            'state'       => self::UNKNOWN_ENTITY_ID,
            'name'        => 'Task ID',
            'description' => 'ID of the duplicating task.',
            'required'    => true,
        ]);

        $this->commandBus->handle($command);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var State $state */
        [/* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Duplicated'], ['id' => 'ASC']);

        $command = new CreateIssueFieldCommand([
            'state'       => $state->id,
            'name'        => 'Task ID',
            'description' => 'ID of the duplicating task.',
            'required'    => true,
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnlockedTemplate()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Duplicated'], ['id' => 'ASC']);

        $command = new CreateIssueFieldCommand([
            'state'       => $state->id,
            'name'        => 'Task ID',
            'description' => 'ID of the duplicating task.',
            'required'    => true,
        ]);

        $this->commandBus->handle($command);
    }

    public function testNameConflict()
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Field with specified name already exists.');

        $this->loginAs('admin@example.com');

        /** @var State $state */
        [/* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Duplicated'], ['id' => 'ASC']);

        $command = new CreateIssueFieldCommand([
            'state'       => $state->id,
            'name'        => 'Issue ID',
            'description' => 'ID of the duplicating task.',
            'required'    => true,
        ]);

        $this->commandBus->handle($command);
    }
}
