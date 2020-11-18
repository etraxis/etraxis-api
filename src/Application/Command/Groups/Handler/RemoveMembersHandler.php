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

namespace eTraxis\Application\Command\Groups\Handler;

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\Application\Command\Groups\RemoveMembersCommand;
use eTraxis\Entity\User;
use eTraxis\Repository\Contracts\GroupRepositoryInterface;
use eTraxis\Voter\GroupVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class RemoveMembersHandler
{
    private AuthorizationCheckerInterface $security;
    private GroupRepositoryInterface      $repository;
    private EntityManagerInterface        $manager;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param GroupRepositoryInterface      $repository
     * @param EntityManagerInterface        $manager
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        GroupRepositoryInterface      $repository,
        EntityManagerInterface        $manager
    )
    {
        $this->security   = $security;
        $this->repository = $repository;
        $this->manager    = $manager;
    }

    /**
     * Command handler.
     *
     * @param RemoveMembersCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(RemoveMembersCommand $command): void
    {
        /** @var null|\eTraxis\Entity\Group $group */
        $group = $this->repository->find($command->group);

        if (!$group) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(GroupVoter::MANAGE_MEMBERSHIP, $group)) {
            throw new AccessDeniedHttpException();
        }

        $query = $this->manager->createQueryBuilder();

        $query
            ->select('user')
            ->from(User::class, 'user')
            ->where($query->expr()->in('user.id', ':users'));

        /** @var User[] $users */
        $users = $query->getQuery()->execute([
            'users' => $command->users,
        ]);

        foreach ($users as $user) {
            $group->removeMember($user);
        }

        $this->repository->persist($group);
    }
}
