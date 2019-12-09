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

use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

/**
 * @coversDefaultClass \eTraxis\MessageBus\EventBus
 */
class EventBusTest extends TestCase
{
    /**
     * @var EventBus
     */
    private $eventBus;

    protected function setUp()
    {
        parent::setUp();

        $messageBus = new class() implements MessageBusInterface {
            public function dispatch($message, array $stamps = []): Envelope
            {
                $envelope = $message instanceof Envelope
                    ? $message
                    : new Envelope($message);

                /** @var callable $callable */
                $callable = $envelope->getMessage();

                $callable($envelope->last(DispatchAfterCurrentBusStamp::class));

                return $envelope
                    ->with(new BusNameStamp('event.bus'));
            }
        };

        $this->eventBus = new EventBus($messageBus);
    }

    /**
     * @covers ::send
     */
    public function testSend()
    {
        $event = new class() {
            public $stamp;

            public function __invoke($stamp)
            {
                $this->stamp = $stamp;
            }
        };

        $this->eventBus->send($event);

        self::assertNotNull($event->stamp);
        self::assertInstanceOf(DispatchAfterCurrentBusStamp::class, $event->stamp);
    }

    /**
     * @covers ::sendAsync
     */
    public function testSendAsync()
    {
        $event = new class() {
            public $stamp;

            public function __invoke($stamp)
            {
                $this->stamp = $stamp;
            }
        };

        $this->eventBus->sendAsync($event);

        self::assertNull($event->stamp);
    }
}
