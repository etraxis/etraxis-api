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

use eTraxis\Application\Command\Users\UnlockUserCommand;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use eTraxis\Voter\UserVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class UnlockUserHandler
{
    private AuthorizationCheckerInterface $security;
    private UserRepositoryInterface       $repository;

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
     * @param UnlockUserCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(UnlockUserCommand $command): void
    {
        /** @var null|\eTraxis\Entity\User $user */
        $user = $this->repository->find($command->user);

        if (!$user) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(UserVoter::UNLOCK_USER, $user)) {
            throw new AccessDeniedHttpException();
        }

        if (!$user->isAccountNonLocked()) {

            $user->unlockAccount();

            $this->repository->persist($user);
        }
    }
}
