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

use eTraxis\Application\Event\Users\LoginSuccessfulEvent;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;

/**
 * Clears locks count for specified account.
 */
class UnlockAccount implements MessageSubscriberInterface
{
    private $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param UserRepositoryInterface $repository
     */
    public function __construct(UserRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Event subscriber.
     *
     * @param LoginSuccessfulEvent $event
     */
    public function __invoke(LoginSuccessfulEvent $event): void
    {
        /** @var \eTraxis\Entity\User $user */
        $user = $this->repository->findOneByUsername($event->username);

        if ($user !== null) {

            $user->unlockAccount();

            $this->repository->persist($user);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getHandledMessages(): iterable
    {
        yield LoginSuccessfulEvent::class;
    }
}
