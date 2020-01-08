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

use eTraxis\Application\Command\Users\UpdateSettingsCommand;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Command handler.
 */
class UpdateSettingsHandler
{
    private $tokenStorage;
    private $session;
    private $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param TokenStorageInterface   $tokenStorage
     * @param SessionInterface        $session
     * @param UserRepositoryInterface $repository
     */
    public function __construct(
        TokenStorageInterface   $tokenStorage,
        SessionInterface        $session,
        UserRepositoryInterface $repository
    )
    {
        $this->tokenStorage = $tokenStorage;
        $this->session      = $session;
        $this->repository   = $repository;
    }

    /**
     * Command handler.
     *
     * @param UpdateSettingsCommand $command
     *
     * @throws AccessDeniedHttpException
     */
    public function __invoke(UpdateSettingsCommand $command): void
    {
        $token = $this->tokenStorage->getToken();

        // User must be logged in.
        if (!$token) {
            throw new AccessDeniedHttpException();
        }

        /** @var \eTraxis\Entity\User $user */
        $user = $token->getUser();

        $user->locale   = $command->locale;
        $user->theme    = $command->theme;
        $user->timezone = $command->timezone;

        $this->repository->persist($user);

        $this->session->set('_locale', $user->locale);
    }
}
