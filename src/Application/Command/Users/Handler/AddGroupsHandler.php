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
use eTraxis\Application\Command\Users\AddGroupsCommand;
use eTraxis\Entity\Group;
use eTraxis\Repository\Contracts\GroupRepositoryInterface;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use eTraxis\Voter\GroupVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class AddGroupsHandler
{
    private $security;
    private $userRepository;
    private $groupRepository;
    private $manager;

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
     * @param AddGroupsCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(AddGroupsCommand $command): void
    {
        /** @var null|\eTraxis\Entity\User $user */
        $user = $this->userRepository->find($command->user);

        if (!$user) {
            throw new NotFoundHttpException();
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

            if (!$this->security->isGranted(GroupVoter::MANAGE_MEMBERSHIP, $group)) {
                throw new AccessDeniedHttpException();
            }

            $group->addMember($user);
            $this->groupRepository->persist($group);
        }
    }
}
