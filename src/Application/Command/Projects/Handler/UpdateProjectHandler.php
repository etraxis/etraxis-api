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

namespace eTraxis\Application\Command\Projects\Handler;

use eTraxis\Application\Command\Projects\UpdateProjectCommand;
use eTraxis\Repository\Contracts\ProjectRepositoryInterface;
use eTraxis\Voter\ProjectVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
class UpdateProjectHandler
{
    private AuthorizationCheckerInterface $security;
    private ValidatorInterface            $validator;
    private ProjectRepositoryInterface    $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param ValidatorInterface            $validator
     * @param ProjectRepositoryInterface    $repository
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        ValidatorInterface            $validator,
        ProjectRepositoryInterface    $repository
    )
    {
        $this->security   = $security;
        $this->validator  = $validator;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param UpdateProjectCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(UpdateProjectCommand $command)
    {
        /** @var null|\eTraxis\Entity\Project $project */
        $project = $this->repository->find($command->project);

        if (!$project) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(ProjectVoter::UPDATE_PROJECT, $project)) {
            throw new AccessDeniedHttpException();
        }

        $project->name        = $command->name;
        $project->description = $command->description;
        $project->isSuspended = $command->suspended;

        $errors = $this->validator->validate($project);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->repository->persist($project);
    }
}
