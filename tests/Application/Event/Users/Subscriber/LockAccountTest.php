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

namespace eTraxis\Application\Event\Users\Subscriber;

use eTraxis\Application\Event\Users\LoginFailedEvent;
use eTraxis\Entity\User;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use eTraxis\TransactionalTestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @coversDefaultClass \eTraxis\Application\Event\Users\Subscriber\LockAccount
 */
class LockAccountTest extends TransactionalTestCase
{
    private LoggerInterface         $logger;
    private UserRepositoryInterface $repository;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->logger     = new NullLogger();
        $this->repository = $this->doctrine->getRepository(User::class);
    }

    /**
     * @covers ::__invoke
     */
    public function testLockUser()
    {
        $event = new LoginFailedEvent([
            'username' => 'artem@example.com',
        ]);

        $handler = new LockAccount($this->logger, $this->repository, 2, 10);

        // first time
        $handler($event);

        /** @var User $user */
        $user = $this->repository->loadUserByUsername('artem@example.com');
        static::assertTrue($user->isAccountNonLocked());

        // second time
        $handler($event);

        $user = $this->repository->loadUserByUsername('artem@example.com');
        static::assertFalse($user->isAccountNonLocked());
    }

    /**
     * @covers ::__invoke
     */
    public function testLockUserForever()
    {
        $event = new LoginFailedEvent([
            'username' => 'artem@example.com',
        ]);

        $handler = new LockAccount($this->logger, $this->repository, 2, null);

        // first time
        $handler($event);

        /** @var User $user */
        $user = $this->repository->loadUserByUsername('artem@example.com');
        static::assertTrue($user->isAccountNonLocked());

        // second time
        $handler($event);

        $user = $this->repository->loadUserByUsername('artem@example.com');
        static::assertFalse($user->isAccountNonLocked());
    }

    /**
     * @covers ::__invoke
     */
    public function testNoLock()
    {
        $event = new LoginFailedEvent([
            'username' => 'artem@example.com',
        ]);

        $handler = new LockAccount($this->logger, $this->repository, null, null);

        // first time
        $handler($event);

        /** @var User $user */
        $user = $this->repository->loadUserByUsername('artem@example.com');
        static::assertTrue($user->isAccountNonLocked());

        // second time
        $handler($event);

        $user = $this->repository->loadUserByUsername('artem@example.com');
        static::assertTrue($user->isAccountNonLocked());
    }

    /**
     * @covers ::getHandledMessages
     */
    public function testGetHandledMessages()
    {
        /** @var \Traversable $events */
        $events = LockAccount::getHandledMessages();
        static::assertContains(LoginFailedEvent::class, iterator_to_array($events));
    }
}
