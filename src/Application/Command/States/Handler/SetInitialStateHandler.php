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
use eTraxis\Application\Command\States\SetInitialStateCommand;
use eTraxis\Application\Dictionary\StateType;
use eTraxis\Entity\State;
use eTraxis\Repository\Contracts\StateRepositoryInterface;
use eTraxis\Voter\StateVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class SetInitialStateHandler
{
    private AuthorizationCheckerInterface $security;
    private StateRepositoryInterface      $repository;
    private EntityManagerInterface        $manager;

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
     * @param SetInitialStateCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(SetInitialStateCommand $command): void
    {
        /** @var null|State $state */
        $state = $this->repository->find($command->state);

        if (!$state) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(StateVoter::SET_INITIAL, $state)) {
            throw new AccessDeniedHttpException();
        }

        if ($state->type !== StateType::INITIAL) {

            // Only one initial state is allowed per template.
            $query = $this->manager->createQuery('
                UPDATE eTraxis:State state
                SET state.type = :interim
                WHERE state.template = :template AND state.type = :initial
            ');

            $query->execute([
                'template' => $state->template,
                'initial'  => StateType::INITIAL,
                'interim'  => StateType::INTERMEDIATE,
            ]);

            $reflection = new \ReflectionProperty(State::class, 'type');
            $reflection->setAccessible(true);
            $reflection->setValue($state, StateType::INITIAL);

            $this->repository->persist($state);
        }
    }
}
