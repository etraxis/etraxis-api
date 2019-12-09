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
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * @coversDefaultClass \eTraxis\MessageBus\AbstractBus
 */
class AbstractBusTest extends TestCase
{
    /**
     * @var AbstractBus
     */
    private $bus;

    protected function setUp()
    {
        parent::setUp();

        $this->bus = new class() extends AbstractBus implements MessageBusInterface {
            public function dispatch($message, array $stamps = []): Envelope
            {
                $this->assertBusName('test.bus', $message);

                $result = [
                    'firstName' => 'Anna',
                    'lastName'  => 'Rodygina',
                ];

                return $message->with(new HandledStamp($result, 'test.handler'));
            }

            public function getResult(Envelope $envelope)
            {
                return $this->getMessageResult($envelope);
            }
        };
    }

    /**
     * @covers ::assertBusName
     */
    public function testAssertBusNameSuccess()
    {
        $message  = new \stdClass();
        $stamp    = new BusNameStamp('test.bus');
        $envelope = new Envelope($message, [$stamp]);

        self::assertNull($envelope->last(HandledStamp::class));

        $envelope = $this->bus->dispatch($envelope);

        self::assertNotNull($envelope->last(HandledStamp::class));
    }

    /**
     * @covers ::assertBusName
     */
    public function testAssertBusNameException()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The stdClass message must be handled by the "test.bus" service but was dispatched to the "another.bus" one.');

        $message  = new \stdClass();
        $stamp    = new BusNameStamp('another.bus');
        $envelope = new Envelope($message, [$stamp]);

        self::assertNull($envelope->last(HandledStamp::class));

        $envelope = $this->bus->dispatch($envelope);

        self::assertNotNull($envelope->last(HandledStamp::class));
    }

    /**
     * @covers ::getMessageResult
     */
    public function testGetMessageResult()
    {
        $expected = [
            'firstName' => 'Anna',
            'lastName'  => 'Rodygina',
        ];

        $message  = new \stdClass();
        $stamp    = new BusNameStamp('test.bus');
        $envelope = new Envelope($message, [$stamp]);

        $envelope = $this->bus->dispatch($envelope);

        self::assertSame($expected, $this->bus->getResult($envelope));
    }
}
