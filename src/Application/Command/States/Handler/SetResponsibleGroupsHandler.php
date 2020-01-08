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

namespace eTraxis\Application\Command\States\Handler;

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\Application\Command\States\SetResponsibleGroupsCommand;
use eTraxis\Entity\Group;
use eTraxis\Entity\StateResponsibleGroup;
use eTraxis\Repository\Contracts\StateRepositoryInterface;
use eTraxis\Voter\StateVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class SetResponsibleGroupsHandler
{
    private $security;
    private $repository;
    private $manager;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param StateRepositoryInterface      $repository
     * @param EntityManagerInterface        $manager
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        StateRepositoryInterface      $repository,
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
     * @param SetResponsibleGroupsCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(SetResponsibleGroupsCommand $command): void
    {
        /** @var null|\eTraxis\Entity\State $state */
        $state = $this->repository->find($command->state);

        if (!$state) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(StateVoter::SET_RESPONSIBLE_GROUPS, $state)) {
            throw new AccessDeniedHttpException();
        }

        $query = $this->manager->createQueryBuilder();

        $query
            ->select('grp')
            ->from(Group::class, 'grp')
            ->where($query->expr()->in('grp.id', ':groups'));

        $requestedGroups = $query->getQuery()->execute([
            'groups' => $command->groups,
        ]);

        foreach ($state->responsibleGroups as $responsibleGroup) {
            if (!in_array($responsibleGroup->group, $requestedGroups, true)) {
                $this->manager->remove($responsibleGroup);
            }
        }

        $existingGroups = array_map(function (StateResponsibleGroup $responsibleGroup) {
            return $responsibleGroup->group;
        }, $state->responsibleGroups);

        foreach ($requestedGroups as $group) {
            if (!in_array($group, $existingGroups, true)) {
                $responsibleGroup = new StateResponsibleGroup($state, $group);
                $this->manager->persist($responsibleGroup);
            }
        }
    }
}
