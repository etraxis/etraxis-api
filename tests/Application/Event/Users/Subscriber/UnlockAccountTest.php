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
use eTraxis\Entity\User;
use eTraxis\TransactionalTestCase;

/**
 * @coversDefaultClass \eTraxis\Application\Event\Users\Subscriber\UnlockAccount
 */
class UnlockAccountTest extends TransactionalTestCase
{
    /**
     * @covers ::__invoke
     */
    public function testUnlockUser()
    {
        /** @var \eTraxis\Repository\Contracts\UserRepositoryInterface $repository */
        $repository = $this->doctrine->getRepository(User::class);

        /** @var User $user */
        $user = $repository->findOneByUsername('artem@example.com');
        $user->lockAccount();

        self::assertFalse($user->isAccountNonLocked());

        $event = new LoginSuccessfulEvent([
            'username' => $user->getUsername(),
        ]);

        $this->eventBus->sendAsync($event);

        self::assertTrue($user->isAccountNonLocked());
    }

    /**
     * @covers ::getHandledMessages
     */
    public function testGetHandledMessages()
    {
        /** @var \Traversable $events */
        $events = UnlockAccount::getHandledMessages();
        self::assertContains(LoginSuccessfulEvent::class, iterator_to_array($events));
    }
}
