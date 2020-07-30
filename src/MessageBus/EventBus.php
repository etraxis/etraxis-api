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

namespace eTraxis\MessageBus;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

class EventBus extends AbstractBus implements Contracts\EventBusInterface
{
    private const BUS_NAME = 'event.bus';

    private MessageBusInterface $eventBus;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param MessageBusInterface $eventBus
     */
    public function __construct(MessageBusInterface $eventBus)
    {
        $this->eventBus = $eventBus;
    }

    /**
     * {@inheritdoc}
     */
    public function send($event): void
    {
        $stamp   = new DispatchAfterCurrentBusStamp();
        $message = new Envelope($event, [$stamp]);

        $this->sendAsync($message);
    }

    /**
     * {@inheritdoc}
     */
    public function sendAsync($event): void
    {
        $envelope = $this->eventBus->dispatch($event);

        $this->assertBusName(self::BUS_NAME, $envelope);
    }
}
