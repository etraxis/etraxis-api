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

namespace eTraxis\MessageBus;

use eTraxis\MessageBus\Contracts\CommandBusInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * @coversDefaultClass \eTraxis\MessageBus\CommandBus
 */
class CommandBusTest extends TestCase
{
    private CommandBusInterface $commandBus;

    protected function setUp()
    {
        parent::setUp();

        $messageBus = new class() implements MessageBusInterface {
            public function dispatch($message, array $stamps = []): Envelope
            {
                $result = [
                    'firstName' => 'Anna',
                    'lastName'  => 'Rodygina',
                ];

                $envelope = new Envelope($message);

                return $envelope
                    ->with(new BusNameStamp('command.bus'))
                    ->with(new HandledStamp($result, 'test_handler'));
            }
        };

        $this->commandBus = new CommandBus($messageBus);
    }

    /**
     * @covers ::handle
     */
    public function testHandle()
    {
        $expected = [
            'firstName' => 'Anna',
            'lastName'  => 'Rodygina',
        ];

        $command = new \stdClass();

        $result = $this->commandBus->handle($command);

        static::assertSame($expected, $result);
    }
}
