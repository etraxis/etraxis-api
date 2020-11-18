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

namespace eTraxis\Application\Command\States\Handler;

use eTraxis\Application\Command\States\UpdateStateCommand;
use eTraxis\Repository\Contracts\StateRepositoryInterface;
use eTraxis\Voter\StateVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
class UpdateStateHandler
{
    private AuthorizationCheckerInterface $security;
    private ValidatorInterface            $validator;
    private StateRepositoryInterface      $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param ValidatorInterface            $validator
     * @param StateRepositoryInterface      $repository
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        ValidatorInterface            $validator,
        StateRepositoryInterface      $repository
    )
    {
        $this->security   = $security;
        $this->validator  = $validator;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param UpdateStateCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(UpdateStateCommand $command): void
    {
        /** @var null|\eTraxis\Entity\State $state */
        $state = $this->repository->find($command->state);

        if (!$state) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(StateVoter::UPDATE_STATE, $state)) {
            throw new AccessDeniedHttpException();
        }

        $state->name        = $command->name;
        $state->responsible = $command->responsible;

        if ($command->next) {

            /** @var null|\eTraxis\Entity\State $nextState */
            $nextState = $this->repository->find($command->next);

            if (!$nextState || $nextState->template !== $state->template) {
                throw new NotFoundHttpException('Unknown next state.');
            }

            $state->nextState = $nextState;
        }

        $errors = $this->validator->validate($state);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->repository->persist($state);
    }
}
