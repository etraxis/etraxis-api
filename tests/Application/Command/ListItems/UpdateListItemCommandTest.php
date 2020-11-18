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

namespace eTraxis\Application\Command\ListItems;

use eTraxis\Entity\ListItem;
use eTraxis\Repository\Contracts\ListItemRepositoryInterface;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @covers \eTraxis\Application\Command\ListItems\Handler\UpdateListItemHandler::__invoke
 */
class UpdateListItemCommandTest extends TransactionalTestCase
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

    public function testSuccessValue()
    {
        $this->loginAs('admin@example.com');

        /** @var ListItem $item */
        [/* skipping */, $item] = $this->repository->findBy(['value' => 3], ['id' => 'ASC']);

        self::assertSame(3, $item->value);
        self::assertSame('low', $item->text);

        $command = new UpdateListItemCommand([
            'item'  => $item->id,
            'value' => 5,
            'text'  => 'low',
        ]);

        $this->commandBus->handle($command);

        /** @var ListItem $item */
        $item = $this->repository->find($item->id);

        self::assertSame(5, $item->value);
        self::assertSame('low', $item->text);
    }

    public function testSuccessText()
    {
        $this->loginAs('admin@example.com');

        /** @var ListItem $item */
        [/* skipping */, $item] = $this->repository->findBy(['value' => 1], ['id' => 'ASC']);

        self::assertSame(1, $item->value);
        self::assertSame('high', $item->text);

        $command = new UpdateListItemCommand([
            'item'  => $item->id,
            'value' => 1,
            'text'  => 'critical',
        ]);

        $this->commandBus->handle($command);

        /** @var ListItem $item */
        $item = $this->repository->find($item->id);

        self::assertSame(1, $item->value);
        self::assertSame('critical', $item->text);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var ListItem $item */
        [/* skipping */, $item] = $this->repository->findBy(['value' => 1], ['id' => 'ASC']);

        $command = new UpdateListItemCommand([
            'item'  => $item->id,
            'value' => 1,
            'text'  => 'critical',
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnlockedTemplate()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var ListItem $item */
        [$item] = $this->repository->findBy(['value' => 1], ['id' => 'ASC']);

        $command = new UpdateListItemCommand([
            'item'  => $item->id,
            'value' => 1,
            'text'  => 'critical',
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownItem()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        $command = new UpdateListItemCommand([
            'item'  => self::UNKNOWN_ENTITY_ID,
            'value' => 1,
            'text'  => 'critical',
        ]);

        $this->commandBus->handle($command);
    }

    public function testValueConflict()
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Item with specified value already exists.');

        $this->loginAs('admin@example.com');

        /** @var ListItem $item */
        [/* skipping */, $item] = $this->repository->findBy(['value' => 1], ['id' => 'ASC']);

        $command = new UpdateListItemCommand([
            'item'  => $item->id,
            'value' => 2,
            'text'  => 'critical',
        ]);

        $this->commandBus->handle($command);
    }

    public function testTextConflict()
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Item with specified text already exists.');

        $this->loginAs('admin@example.com');

        /** @var ListItem $item */
        [/* skipping */, $item] = $this->repository->findBy(['value' => 1], ['id' => 'ASC']);

        $command = new UpdateListItemCommand([
            'item'  => $item->id,
            'value' => 1,
            'text'  => 'normal',
        ]);

        $this->commandBus->handle($command);
    }
}
