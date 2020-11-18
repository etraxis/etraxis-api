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

use eTraxis\Application\Command\Groups\CreateGroupCommand;
use eTraxis\Entity\Group;
use eTraxis\Repository\Contracts\GroupRepositoryInterface;
use eTraxis\Repository\Contracts\ProjectRepositoryInterface;
use eTraxis\Voter\GroupVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
class CreateGroupHandler
{
    private AuthorizationCheckerInterface $security;
    private ValidatorInterface            $validator;
    private ProjectRepositoryInterface    $projectRepository;
    private GroupRepositoryInterface      $groupRepository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param ValidatorInterface            $validator
     * @param ProjectRepositoryInterface    $projectRepository
     * @param GroupRepositoryInterface      $groupRepository
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        ValidatorInterface            $validator,
        ProjectRepositoryInterface    $projectRepository,
        GroupRepositoryInterface      $groupRepository
    )
    {
        $this->security          = $security;
        $this->validator         = $validator;
        $this->projectRepository = $projectRepository;
        $this->groupRepository   = $groupRepository;
    }

    /**
     * Command handler.
     *
     * @param CreateGroupCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     *
     * @return Group
     */
    public function __invoke(CreateGroupCommand $command): Group
    {
        if (!$this->security->isGranted(GroupVoter::CREATE_GROUP)) {
            throw new AccessDeniedHttpException();
        }

        /** @var null|\eTraxis\Entity\Project $project */
        $project = null;

        if ($command->project) {

            $project = $this->projectRepository->find($command->project);

            if (!$project) {
                throw new NotFoundHttpException();
            }
        }

        $group = new Group($project);

        $group->name        = $command->name;
        $group->description = $command->description;

        $errors = $this->validator->validate($group);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->groupRepository->persist($group);

        return $group;
    }
}
