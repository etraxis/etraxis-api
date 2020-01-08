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
use eTraxis\Application\Command\States\SetRolesTransitionCommand;
use eTraxis\Entity\StateRoleTransition;
use eTraxis\Repository\Contracts\StateRepositoryInterface;
use eTraxis\Voter\StateVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class SetRolesTransitionHandler
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
     * @param SetRolesTransitionCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(SetRolesTransitionCommand $command): void
    {
        /** @var null|\eTraxis\Entity\State $fromState */
        $fromState = $this->repository->find($command->from);

        /** @var null|\eTraxis\Entity\State $toState */
        $toState = $this->repository->find($command->to);

        if (!$fromState || !$toState) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(StateVoter::SET_TRANSITIONS, $fromState)) {
            throw new AccessDeniedHttpException();
        }

        // Remove all roles which are supposed to not be granted for specified transition, but they currently are.
        $transitions = array_filter($fromState->roleTransitions, function (StateRoleTransition $transition) use ($command) {
            return $transition->toState->id === $command->to;
        });

        foreach ($transitions as $transition) {
            if (!in_array($transition->role, $command->roles, true)) {
                $this->manager->remove($transition);
            }
        }

        // Add all roles which are supposed to be granted for specified transition, but they currently are not.
        $existingRoles = array_map(function (StateRoleTransition $transition) {
            return $transition->role;
        }, $transitions);

        foreach ($command->roles as $role) {
            if (!in_array($role, $existingRoles, true)) {
                $transition = new StateRoleTransition($fromState, $toState, $role);
                $this->manager->persist($transition);
            }
        }
    }
}
