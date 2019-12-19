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

use eTraxis\Application\Command\Users\DeleteUserCommand;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use eTraxis\Voter\UserVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class DeleteUserHandler
{
    private $security;
    private $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param UserRepositoryInterface       $repository
     */
    public function __construct(AuthorizationCheckerInterface $security, UserRepositoryInterface $repository)
    {
        $this->security   = $security;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param DeleteUserCommand $command
     *
     * @throws AccessDeniedHttpException
     */
    public function __invoke(DeleteUserCommand $command): void
    {
        /** @var null|\eTraxis\Entity\User $user */
        $user = $this->repository->find($command->user);

        if ($user) {

            if (!$this->security->isGranted(UserVoter::DELETE_USER, $user)) {
                throw new AccessDeniedHttpException();
            }

            $this->repository->remove($user);
        }
    }
}
