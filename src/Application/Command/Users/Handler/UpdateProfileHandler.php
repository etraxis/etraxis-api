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

use eTraxis\Application\Command\Users\UpdateProfileCommand;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
class UpdateProfileHandler
{
    private ValidatorInterface      $validator;
    private TokenStorageInterface   $tokenStorage;
    private UserRepositoryInterface $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param ValidatorInterface      $validator
     * @param TokenStorageInterface   $tokenStorage
     * @param UserRepositoryInterface $repository
     */
    public function __construct(
        ValidatorInterface      $validator,
        TokenStorageInterface   $tokenStorage,
        UserRepositoryInterface $repository
    )
    {
        $this->validator    = $validator;
        $this->tokenStorage = $tokenStorage;
        $this->repository   = $repository;
    }

    /**
     * Command handler.
     *
     * @param UpdateProfileCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     */
    public function __invoke(UpdateProfileCommand $command): void
    {
        $token = $this->tokenStorage->getToken();

        // User must be logged in.
        if (!$token) {
            throw new AccessDeniedHttpException();
        }

        /** @var \eTraxis\Entity\User $user */
        $user = $token->getUser();

        if ($user->isAccountExternal()) {
            throw new AccessDeniedHttpException();
        }

        $user->email    = $command->email;
        $user->fullname = $command->fullname;

        $errors = $this->validator->validate($user);

        if (count($errors)) {
            // Emails are used as usernames, so restore the entity to avoid impersonation.
            $this->repository->refresh($user);

            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->repository->persist($user);
    }
}
