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

namespace eTraxis\Application\Command\Groups;

use eTraxis\Entity\Group;
use eTraxis\Repository\Contracts\GroupRepositoryInterface;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @covers \eTraxis\Application\Command\Groups\Handler\DeleteGroupHandler::__invoke
 */
class DeleteGroupCommandTest extends TransactionalTestCase
{
    private GroupRepositoryInterface $repository;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Group::class);
    }

    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var Group $group */
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);
        static::assertNotNull($group);

        $command = new DeleteGroupCommand([
            'group' => $group->id,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->clear();

        $group = $this->repository->find($command->group);
        static::assertNull($group);
    }

    public function testUnknown()
    {
        $this->loginAs('admin@example.com');

        $command = new DeleteGroupCommand([
            'group' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandBus->handle($command);

        static::assertTrue(true);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var Group $group */
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $command = new DeleteGroupCommand([
            'group' => $group->id,
        ]);

        $this->commandBus->handle($command);
    }
}
