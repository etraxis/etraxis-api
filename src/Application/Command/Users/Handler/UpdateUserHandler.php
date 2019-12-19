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

use eTraxis\Application\Command\Users\UpdateUserCommand;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use eTraxis\Voter\UserVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
class UpdateUserHandler
{
    private $security;
    private $validator;
    private $tokens;
    private $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param ValidatorInterface            $validator
     * @param TokenStorageInterface         $tokens
     * @param UserRepositoryInterface       $repository
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        ValidatorInterface            $validator,
        TokenStorageInterface         $tokens,
        UserRepositoryInterface       $repository
    )
    {
        $this->security   = $security;
        $this->validator  = $validator;
        $this->tokens     = $tokens;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param UpdateUserCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(UpdateUserCommand $command): void
    {
        /** @var null|\eTraxis\Entity\User $user */
        $user = $this->repository->find($command->user);

        if (!$user) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(UserVoter::UPDATE_USER, $user)) {
            throw new AccessDeniedHttpException();
        }

        $user->email       = $command->email;
        $user->fullname    = $command->fullname;
        $user->description = $command->description;
        $user->locale      = $command->locale;
        $user->timezone    = $command->timezone;

        /** @var \eTraxis\Entity\User $current */
        $current = $this->tokens->getToken()->getUser();

        // Don't disable yourself.
        if ($user->id !== $current->id) {
            $user->isAdmin = $command->admin;
            $user->setEnabled(!$command->disabled);
        }

        $errors = $this->validator->validate($user);

        if (count($errors)) {
            // Emails are used as usernames, so restore the entity to avoid impersonation.
            $this->repository->refresh($user);

            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->repository->persist($user);
    }
}
