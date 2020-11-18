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

namespace eTraxis\Application\Command\Users\Handler;

use eTraxis\Application\Command\Users\SetPasswordCommand;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use eTraxis\Voter\UserVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * Command handler.
 */
class SetPasswordHandler
{
    private AuthorizationCheckerInterface $security;
    private UserPasswordEncoderInterface  $encoder;
    private UserRepositoryInterface       $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param UserPasswordEncoderInterface  $encoder
     * @param UserRepositoryInterface       $repository
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        UserPasswordEncoderInterface  $encoder,
        UserRepositoryInterface       $repository
    )
    {
        $this->security   = $security;
        $this->encoder    = $encoder;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param SetPasswordCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(SetPasswordCommand $command): void
    {
        /** @var null|\eTraxis\Entity\User $user */
        $user = $this->repository->find($command->user);

        if (!$user) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(UserVoter::SET_PASSWORD, $user)) {
            throw new AccessDeniedHttpException();
        }

        try {
            $user->password = $this->encoder->encodePassword($user, $command->password);
        }
        catch (BadCredentialsException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        $this->repository->persist($user);
    }
}
