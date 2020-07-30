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

namespace eTraxis\Application\Command\Groups\Handler;

use eTraxis\Application\Command\Groups\DeleteGroupCommand;
use eTraxis\Repository\Contracts\GroupRepositoryInterface;
use eTraxis\Voter\GroupVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class DeleteGroupHandler
{
    private AuthorizationCheckerInterface $security;
    private GroupRepositoryInterface      $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param GroupRepositoryInterface      $repository
     */
    public function __construct(AuthorizationCheckerInterface $security, GroupRepositoryInterface $repository)
    {
        $this->security   = $security;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param DeleteGroupCommand $command
     *
     * @throws AccessDeniedHttpException
     */
    public function __invoke(DeleteGroupCommand $command): void
    {
        /** @var null|\eTraxis\Entity\Group $group */
        $group = $this->repository->find($command->group);

        if ($group) {

            if (!$this->security->isGranted(GroupVoter::DELETE_GROUP, $group)) {
                throw new AccessDeniedHttpException();
            }

            $this->repository->remove($group);
        }
    }
}
