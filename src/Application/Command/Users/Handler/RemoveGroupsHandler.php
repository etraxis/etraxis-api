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

namespace eTraxis\Application\Command\Users\Handler;

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\Application\Command\Users\RemoveGroupsCommand;
use eTraxis\Entity\Group;
use eTraxis\Repository\Contracts\GroupRepositoryInterface;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use eTraxis\Voter\UserVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class RemoveGroupsHandler
{
    private AuthorizationCheckerInterface $security;
    private UserRepositoryInterface       $userRepository;
    private GroupRepositoryInterface      $groupRepository;
    private EntityManagerInterface        $manager;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param UserRepositoryInterface       $userRepository
     * @param GroupRepositoryInterface      $groupRepository
     * @param EntityManagerInterface        $manager
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        UserRepositoryInterface       $userRepository,
        GroupRepositoryInterface      $groupRepository,
        EntityManagerInterface        $manager
    )
    {
        $this->security        = $security;
        $this->userRepository  = $userRepository;
        $this->groupRepository = $groupRepository;
        $this->manager         = $manager;
    }

    /**
     * Command handler.
     *
     * @param RemoveGroupsCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(RemoveGroupsCommand $command): void
    {
        /** @var null|\eTraxis\Entity\User $user */
        $user = $this->userRepository->find($command->user);

        if (!$user) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(UserVoter::MANAGE_MEMBERSHIP, $user)) {
            throw new AccessDeniedHttpException();
        }

        $query = $this->manager->createQueryBuilder();

        $query
            ->select('grp')
            ->from(Group::class, 'grp')
            ->where($query->expr()->in('grp.id', ':groups'));

        /** @var Group[] $groups */
        $groups = $query->getQuery()->execute([
            'groups' => $command->groups,
        ]);

        foreach ($groups as $group) {
            $group->removeMember($user);
            $this->groupRepository->persist($group);
        }
    }
}
