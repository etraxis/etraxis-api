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

namespace eTraxis\Application\Event\Users\Subscriber;

use eTraxis\Application\Event\Users\LoginFailedEvent;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;

/**
 * Increases locks count for specified account.
 */
class LockAccount implements MessageSubscriberInterface
{
    private $logger;
    private $repository;
    private $authFailures;
    private $lockDuration;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param LoggerInterface         $logger
     * @param UserRepositoryInterface $repository
     * @param null|int                $authFailures
     * @param null|int                $lockDuration
     */
    public function __construct(
        LoggerInterface         $logger,
        UserRepositoryInterface $repository,
        ?int                    $authFailures,
        ?int                    $lockDuration
    )
    {
        $this->logger       = $logger;
        $this->repository   = $repository;
        $this->authFailures = $authFailures;
        $this->lockDuration = $lockDuration;
    }

    /**
     * Event subscriber.
     *
     * @param LoginFailedEvent $event
     *
     * @throws \Exception
     */
    public function __invoke(LoginFailedEvent $event): void
    {
        if ($this->authFailures === null) {
            return;
        }

        /** @var \eTraxis\Entity\User $user */
        $user = $this->repository->loadUserByUsername($event->username);

        if ($user !== null) {

            $this->logger->info('Authentication failure', ['username' => $event->username]);

            if ($user->incAuthFailures() >= $this->authFailures) {

                if ($this->lockDuration === null) {
                    $user->lockAccount();
                }
                else {
                    $interval = sprintf('PT%dM', $this->lockDuration);
                    $user->lockAccount(date_create()->add(new \DateInterval($interval)));
                }
            }

            $this->repository->persist($user);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getHandledMessages(): iterable
    {
        yield LoginFailedEvent::class;
    }
}
