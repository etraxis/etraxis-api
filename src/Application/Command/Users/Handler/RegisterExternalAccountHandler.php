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

use eTraxis\Application\Command\Users\RegisterExternalAccountCommand;
use eTraxis\Entity\User;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Command handler.
 */
class RegisterExternalAccountHandler
{
    private LoggerInterface         $logger;
    private UserRepositoryInterface $repository;
    private string                  $locale;
    private string                  $theme;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param LoggerInterface         $logger
     * @param UserRepositoryInterface $repository
     * @param string                  $locale
     * @param string                  $theme
     */
    public function __construct(LoggerInterface $logger, UserRepositoryInterface $repository, string $locale, string $theme)
    {
        $this->logger     = $logger;
        $this->repository = $repository;
        $this->locale     = $locale;
        $this->theme      = $theme;
    }

    /**
     * Command handler.
     *
     * @param RegisterExternalAccountCommand $command
     *
     * @return User
     */
    public function __invoke(RegisterExternalAccountCommand $command): User
    {
        /** @var User $user */
        $user = $this->repository->findOneBy([
            'account.provider' => $command->provider,
            'account.uid'      => $command->uid,
        ]);

        // If we can't find the account by its UID, try to find by the email.
        if ($user === null) {
            $this->logger->info('Cannot find by UID.', [
                'provider' => $command->provider,
                'uid'      => $command->uid,
            ]);

            $user = $this->repository->loadUserByUsername($command->email);
        }

        // Register new account.
        if ($user === null) {
            $this->logger->info('Register external account.', [
                'email'    => $command->email,
                'fullname' => $command->fullname,
            ]);

            $user = new User();

            $user->locale = $this->locale;
            $user->theme  = $this->theme;
        }
        // The account already exists - update it.
        else {
            $this->logger->info('Update external account.', [
                'email'    => $command->email,
                'fullname' => $command->fullname,
            ]);
        }

        $user->account->provider = $command->provider;
        $user->account->uid      = $command->uid;
        $user->email             = $command->email;
        $user->fullname          = $command->fullname;
        $user->password          = null;

        $this->repository->persist($user);

        return $user;
    }
}
