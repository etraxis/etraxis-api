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

namespace eTraxis\Application\Command\Projects\Handler;

use eTraxis\Application\Command\Projects\ResumeProjectCommand;
use eTraxis\Repository\Contracts\ProjectRepositoryInterface;
use eTraxis\Voter\ProjectVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class ResumeProjectHandler
{
    private AuthorizationCheckerInterface $security;
    private ProjectRepositoryInterface    $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param ProjectRepositoryInterface    $repository
     */
    public function __construct(AuthorizationCheckerInterface $security, ProjectRepositoryInterface $repository)
    {
        $this->security   = $security;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param ResumeProjectCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(ResumeProjectCommand $command)
    {
        /** @var null|\eTraxis\Entity\Project $project */
        $project = $this->repository->find($command->project);

        if (!$project) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(ProjectVoter::RESUME_PROJECT, $project)) {
            throw new AccessDeniedHttpException();
        }

        if ($project->isSuspended) {

            $project->isSuspended = false;

            $this->repository->persist($project);
        }
    }
}
