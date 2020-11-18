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

use eTraxis\Entity\Field;
use eTraxis\Entity\State;
use eTraxis\Repository\Contracts\FieldRepositoryInterface;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @covers \eTraxis\Application\Command\Fields\Handler\DeleteFieldHandler::__invoke
 */
class DeleteFieldCommandTest extends TransactionalTestCase
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

    public function testSuccessDelete()
    {
        $this->loginAs('admin@example.com');

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New'], ['id' => 'DESC']);

        self::assertCount(3, $state->fields);

        [$field1, $field2, $field3] = $state->fields;

        self::assertSame(1, $field1->position);
        self::assertSame(2, $field2->position);
        self::assertSame(3, $field3->position);

        $command = new DeleteFieldCommand([
            'field' => $field1->id,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->clear();

        $field = $this->repository->find($command->field);
        self::assertNull($field);

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New'], ['id' => 'DESC']);

        self::assertCount(2, $state->fields);

        [$field1, $field2] = $state->fields;

        self::assertSame(1, $field1->position);
        self::assertSame(2, $field2->position);
    }

    public function testSuccessRemove()
    {
        $this->loginAs('admin@example.com');

        /** @var State $state */
        [/* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New'], ['id' => 'ASC']);

        self::assertCount(3, $state->fields);

        [$field1, $field2, $field3] = $state->fields;

        self::assertSame(1, $field1->position);
        self::assertSame(2, $field2->position);
        self::assertSame(3, $field3->position);

        $command = new DeleteFieldCommand([
            'field' => $field1->id,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->clear();

        /** @var Field $field */
        $field = $this->repository->find($command->field);
        self::assertNotNull($field);
        self::assertTrue($field->isRemoved);
        self::assertSame(1, $field->position);

        /** @var State $state */
        [/* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New'], ['id' => 'ASC']);

        self::assertCount(2, $state->fields);

        [$field1, $field2] = $state->fields;

        self::assertSame(1, $field1->position);
        self::assertSame(2, $field2->position);
    }

    public function testUnknownField()
    {
        $this->loginAs('admin@example.com');

        $command = new DeleteFieldCommand([
            'field' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandBus->handle($command);

        self::assertTrue(true);
    }

    public function testRemovedField()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Task ID'], ['id' => 'DESC']);

        self::assertCount(1, $field->state->fields);

        $command = new DeleteFieldCommand([
            'field' => $field->id,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->clear();

        $field = $this->repository->find($command->field);

        self::assertNotNull($field);
        self::assertTrue($field->isRemoved);
        self::assertCount(1, $field->state->fields);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'DESC']);

        $command = new DeleteFieldCommand([
            'field' => $field->id,
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnlockedTemplate()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'DESC']);

        $field->state->template->isLocked = false;

        $command = new DeleteFieldCommand([
            'field' => $field->id,
        ]);

        $this->commandBus->handle($command);
    }
}
