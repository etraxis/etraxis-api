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

/**
 * @covers \eTraxis\Application\Command\ListItems\Handler\DeleteListItemHandler::__invoke
 */
class DeleteListItemCommandTest extends TransactionalTestCase
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

        /** @var ListItem $item */
        [/* skipping */, $item] = $this->repository->findBy(['value' => 3], ['id' => 'ASC']);
        static::assertNotNull($item);

        $command = new DeleteListItemCommand([
            'item' => $item->id,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->clear();

        $item = $this->repository->find($command->item);
        static::assertNull($item);
    }

    public function testUnknownItem()
    {
        $this->loginAs('admin@example.com');

        $command = new DeleteListItemCommand([
            'item' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandBus->handle($command);

        static::assertTrue(true);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var ListItem $item */
        [/* skipping */, $item] = $this->repository->findBy(['value' => 3], ['id' => 'ASC']);

        $command = new DeleteListItemCommand([
            'item' => $item->id,
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnlockedTemplate()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var ListItem $item */
        [$item] = $this->repository->findBy(['value' => 3], ['id' => 'ASC']);

        $command = new DeleteListItemCommand([
            'item' => $item->id,
        ]);

        $this->commandBus->handle($command);
    }
}
