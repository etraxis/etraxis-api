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
use eTraxis\Application\Command\States\SetGroupsTransitionCommand;
use eTraxis\Entity\Group;
use eTraxis\Entity\StateGroupTransition;
use eTraxis\Repository\Contracts\StateRepositoryInterface;
use eTraxis\Voter\StateVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class SetGroupsTransitionHandler
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
     * @param SetGroupsTransitionCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(SetGroupsTransitionCommand $command): void
    {
        /** @var null|\eTraxis\Entity\State $fromState */
        $fromState = $this->repository->find($command->from);

        /** @var null|\eTraxis\Entity\State $toState */
        $toState = $this->repository->find($command->to);

        if (!$fromState || !$toState) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(StateVoter::MANAGE_TRANSITIONS, $fromState)) {
            throw new AccessDeniedHttpException();
        }

        // Retrieve all groups specified in the command.
        $query = $this->manager->createQueryBuilder();

        $query
            ->select('grp')
            ->from(Group::class, 'grp')
            ->where($query->expr()->in('grp.id', ':groups'));

        $requestedGroups = $query->getQuery()->execute([
            'groups' => $command->groups,
        ]);

        // Remove all groups which are supposed to not be granted for specified transition, but they currently are.
        $transitions = array_filter($fromState->groupTransitions, function (StateGroupTransition $transition) use ($command) {
            return $transition->toState->id === $command->to;
        });

        foreach ($transitions as $transition) {
            if (!in_array($transition->group, $requestedGroups, true)) {
                $this->manager->remove($transition);
            }
        }

        // Add all groups which are supposed to be granted for specified transition, but they currently are not.
        $existingGroups = array_map(function (StateGroupTransition $transition) {
            return $transition->group;
        }, $transitions);

        foreach ($requestedGroups as $group) {
            if (!in_array($group, $existingGroups, true)) {
                $transition = new StateGroupTransition($fromState, $toState, $group);
                $this->manager->persist($transition);
            }
        }
    }
}
