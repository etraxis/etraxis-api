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

namespace eTraxis\Application\Command\Users;

use eTraxis\Entity\Group;
use eTraxis\Entity\User;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @covers \eTraxis\Application\Command\Users\Handler\AddGroupsHandler::__invoke
 */
class AddGroupsCommandTest extends TransactionalTestCase
{
    private UserRepositoryInterface $repository;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(User::class);
    }

    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        $before = [
            'Company Staff',
            'Developers A',
            'Developers B',
        ];

        $after = [
            'Company Staff',
            'Developers A',
            'Developers B',
            'Developers C',
        ];

        /** @var \eTraxis\Repository\Contracts\GroupRepositoryInterface $groupRepository */
        $groupRepository = $this->doctrine->getRepository(Group::class);

        /** @var Group $devB */
        /** @var Group $devC */
        $devB = $groupRepository->findOneBy(['description' => 'Developers B']);
        $devC = $groupRepository->findOneBy(['description' => 'Developers C']);

        /** @var User $user */
        $user = $this->repository->loadUserByUsername('labshire@example.com');

        $groups = array_map(fn (Group $group) => $group->description ?? $group->name, $user->groups);

        sort($groups);
        self::assertSame($before, $groups);

        $command = new AddGroupsCommand([
            'user'   => $user->id,
            'groups' => [
                $devB->id,
                $devC->id,
            ],
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($user);

        $groups = array_map(fn (Group $group) => $group->description ?? $group->name, $user->groups);

        sort($groups);
        self::assertSame($after, $groups);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var Group $devC */
        $devC = $this->doctrine->getRepository(Group::class)->findOneBy(['description' => 'Developers C']);

        /** @var User $user */
        $user = $this->repository->loadUserByUsername('labshire@example.com');

        $command = new AddGroupsCommand([
            'user'   => $user->id,
            'groups' => [
                $devC->id,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownUser()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var Group $devC */
        $devC = $this->doctrine->getRepository(Group::class)->findOneBy(['description' => 'Developers C']);

        $command = new AddGroupsCommand([
            'user'   => self::UNKNOWN_ENTITY_ID,
            'groups' => [
                $devC->id,
            ],
        ]);

        $this->commandBus->handle($command);
    }
}
