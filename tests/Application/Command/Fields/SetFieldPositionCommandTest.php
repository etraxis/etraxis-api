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

namespace eTraxis\Application\Command\Fields;

use eTraxis\Entity\Field;
use eTraxis\Entity\State;
use eTraxis\Repository\Contracts\FieldRepositoryInterface;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @coversDefaultClass \eTraxis\Application\Command\Fields\Handler\SetFieldPositionHandler
 */
class SetFieldPositionCommandTest extends TransactionalTestCase
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

    /**
     * @covers ::__invoke
     * @covers ::setPosition
     */
    public function testSuccessUp()
    {
        $this->loginAs('admin@example.com');

        $expected = [
            'Commit ID',
            'Effort',
            'Delta',
            'Test coverage',
        ];

        /** @var Field $field */
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Effort'], ['id' => 'ASC']);

        $command = new SetFieldPositionCommand([
            'field'    => $field->id,
            'position' => $field->position - 1,
        ]);

        $this->commandBus->handle($command);

        self::assertSame($expected, $this->getFields($field->state));
    }

    /**
     * @covers ::__invoke
     * @covers ::setPosition
     */
    public function testSuccessDown()
    {
        $this->loginAs('admin@example.com');

        $expected = [
            'Commit ID',
            'Effort',
            'Delta',
            'Test coverage',
        ];

        /** @var Field $field */
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Delta'], ['id' => 'ASC']);

        $command = new SetFieldPositionCommand([
            'field'    => $field->id,
            'position' => $field->position + 1,
        ]);

        $this->commandBus->handle($command);

        self::assertSame($expected, $this->getFields($field->state));
    }

    /**
     * @covers ::__invoke
     * @covers ::setPosition
     */
    public function testSuccessTop()
    {
        $this->loginAs('admin@example.com');

        $expected = [
            'Effort',
            'Commit ID',
            'Delta',
            'Test coverage',
        ];

        /** @var Field $field */
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Effort'], ['id' => 'ASC']);

        $command = new SetFieldPositionCommand([
            'field'    => $field->id,
            'position' => 1,
        ]);

        $this->commandBus->handle($command);

        self::assertSame($expected, $this->getFields($field->state));
    }

    /**
     * @covers ::__invoke
     * @covers ::setPosition
     */
    public function testSuccessBottom()
    {
        $this->loginAs('admin@example.com');

        $expected = [
            'Commit ID',
            'Effort',
            'Test coverage',
            'Delta',
        ];

        /** @var Field $field */
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Delta'], ['id' => 'ASC']);

        $command = new SetFieldPositionCommand([
            'field'    => $field->id,
            'position' => PHP_INT_MAX,
        ]);

        $this->commandBus->handle($command);

        self::assertSame($expected, $this->getFields($field->state));
    }

    /**
     * @covers ::__invoke
     */
    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Effort'], ['id' => 'ASC']);

        $command = new SetFieldPositionCommand([
            'field'    => $field->id,
            'position' => 1,
        ]);

        $this->commandBus->handle($command);
    }

    /**
     * @covers ::__invoke
     */
    public function testUnlockedTemplate()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Effort'], ['id' => 'ASC']);

        $command = new SetFieldPositionCommand([
            'field'    => $field->id,
            'position' => 1,
        ]);

        $this->commandBus->handle($command);
    }

    /**
     * @covers ::__invoke
     */
    public function testUnknownField()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        $command = new SetFieldPositionCommand([
            'field'    => self::UNKNOWN_ENTITY_ID,
            'position' => 1,
        ]);

        $this->commandBus->handle($command);
    }

    /**
     * @covers ::__invoke
     */
    public function testRemovedField()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Task ID'], ['id' => 'ASC']);

        $command = new SetFieldPositionCommand([
            'field'    => $field->id,
            'position' => 1,
        ]);

        $this->commandBus->handle($command);
    }

    /**
     * @param State $state
     *
     * @return array
     */
    private function getFields(State $state)
    {
        /** @var Field[] $fields */
        $fields = $this->repository->findBy([
            'state'     => $state,
            'removedAt' => null,
        ], ['position' => 'ASC']);

        return array_map(fn (Field $field) => $field->name, $fields);
    }
}
