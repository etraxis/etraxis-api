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

namespace eTraxis\Application\Command\Groups;

use eTraxis\Entity\Group;
use eTraxis\Entity\User;
use eTraxis\Repository\Contracts\GroupRepositoryInterface;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @covers \eTraxis\Application\Command\Groups\Handler\AddMembersHandler::__invoke
 */
class AddMembersCommandTest extends TransactionalTestCase
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

        $before = [
            'christy.mcdermott@example.com',
            'dquigley@example.com',
            'fdooley@example.com',
            'labshire@example.com',
        ];

        $after = [
            'christy.mcdermott@example.com',
            'dquigley@example.com',
            'fdooley@example.com',
            'labshire@example.com',
            'nhills@example.com',
        ];

        /** @var \eTraxis\Repository\Contracts\UserRepositoryInterface $userRepository */
        $userRepository = $this->doctrine->getRepository(User::class);

        /** @var User $fdooley */
        /** @var User $nhills */
        $fdooley = $userRepository->loadUserByUsername('fdooley@example.com');
        $nhills  = $userRepository->loadUserByUsername('nhills@example.com');

        /** @var Group $group */
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $members = array_map(fn (User $user) => $user->email, $group->members);

        sort($members);
        self::assertSame($before, $members);

        $command = new AddMembersCommand([
            'group' => $group->id,
            'users' => [
                $fdooley->id,
                $nhills->id,
            ],
        ]);

        $this->commandBus->handle($command);

        /** @var Group $group */
        $group = $this->repository->find($group->id);

        $members = array_map(fn (User $user) => $user->email, $group->members);

        sort($members);
        self::assertSame($after, $members);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var User $nhills */
        $nhills = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        /** @var Group $group */
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $command = new AddMembersCommand([
            'group' => $group->id,
            'users' => [
                $nhills->id,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownGroup()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var User $nhills */
        $nhills = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $command = new AddMembersCommand([
            'group' => self::UNKNOWN_ENTITY_ID,
            'users' => [
                $nhills->id,
            ],
        ]);

        $this->commandBus->handle($command);
    }
}
