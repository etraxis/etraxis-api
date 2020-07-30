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

namespace eTraxis\Application\Command\ListItems;

use eTraxis\Entity\Field;
use eTraxis\Entity\ListItem;
use eTraxis\Repository\Contracts\ListItemRepositoryInterface;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @covers \eTraxis\Application\Command\ListItems\Handler\CreateListItemHandler::__invoke
 */
class CreateListItemCommandTest extends TransactionalTestCase
{
    private ListItemRepositoryInterface $repository;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(ListItem::class);
    }

    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var ListItem $item */
        $item = $this->repository->findOneBy(['value' => 4]);
        self::assertNull($item);

        $command = new CreateListItemCommand([
            'field' => $field->id,
            'value' => 4,
            'text'  => 'typo',
        ]);

        $result = $this->commandBus->handle($command);

        /** @var ListItem $item */
        $item = $this->repository->findOneBy(['value' => 4]);
        self::assertInstanceOf(ListItem::class, $item);
        self::assertSame($result, $item);

        self::assertSame($field, $item->field);
        self::assertSame(4, $item->value);
        self::assertSame('typo', $item->text);
    }

    public function testUnknownField()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        $command = new CreateListItemCommand([
            'field' => self::UNKNOWN_ENTITY_ID,
            'value' => 4,
            'text'  => 'typo',
        ]);

        $this->commandBus->handle($command);
    }

    public function testWrongField()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Description'], ['id' => 'ASC']);

        $command = new CreateListItemCommand([
            'field' => $field->id,
            'value' => 4,
            'text'  => 'typo',
        ]);

        $this->commandBus->handle($command);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $command = new CreateListItemCommand([
            'field' => $field->id,
            'value' => 4,
            'text'  => 'typo',
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnlockedTemplate()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $command = new CreateListItemCommand([
            'field' => $field->id,
            'value' => 4,
            'text'  => 'typo',
        ]);

        $this->commandBus->handle($command);
    }

    public function testValueConflict()
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Item with specified value already exists.');

        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $command = new CreateListItemCommand([
            'field' => $field->id,
            'value' => 3,
            'text'  => 'typo',
        ]);

        $this->commandBus->handle($command);
    }

    public function testTextConflict()
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Item with specified text already exists.');

        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $command = new CreateListItemCommand([
            'field' => $field->id,
            'value' => 4,
            'text'  => 'low',
        ]);

        $this->commandBus->handle($command);
    }
}
