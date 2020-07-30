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

use eTraxis\Application\Command\Users\CreateUserCommand;
use eTraxis\Entity\User;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use eTraxis\Voter\UserVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
class CreateUserHandler
{
    private AuthorizationCheckerInterface $security;
    private ValidatorInterface            $validator;
    private UserPasswordEncoderInterface  $encoder;
    private UserRepositoryInterface       $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param ValidatorInterface            $validator
     * @param UserPasswordEncoderInterface  $encoder
     * @param UserRepositoryInterface       $repository
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        ValidatorInterface            $validator,
        UserPasswordEncoderInterface  $encoder,
        UserRepositoryInterface       $repository
    )
    {
        $this->security   = $security;
        $this->validator  = $validator;
        $this->encoder    = $encoder;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param CreateUserCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws BadRequestHttpException
     * @throws ConflictHttpException
     *
     * @return User
     */
    public function __invoke(CreateUserCommand $command): User
    {
        if (!$this->security->isGranted(UserVoter::CREATE_USER)) {
            throw new AccessDeniedHttpException();
        }

        $user = new User();

        $user->email       = $command->email;
        $user->fullname    = $command->fullname;
        $user->description = $command->description;
        $user->isAdmin     = $command->admin;
        $user->locale      = $command->locale;
        $user->theme       = $command->theme;
        $user->timezone    = $command->timezone;

        $user->setEnabled(!$command->disabled);

        try {
            $user->password = $this->encoder->encodePassword($user, $command->password);
        }
        catch (BadCredentialsException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        $errors = $this->validator->validate($user);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->repository->persist($user);

        return $user;
    }
}
