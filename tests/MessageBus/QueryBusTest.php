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

use eTraxis\MessageBus\Contracts\QueryBusInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * @coversDefaultClass \eTraxis\MessageBus\QueryBus
 */
class QueryBusTest extends TestCase
{
    private QueryBusInterface $queryBus;

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
                    ->with(new BusNameStamp('query.bus'))
                    ->with(new HandledStamp($result, 'test_handler'));
            }
        };

        $this->queryBus = new QueryBus($messageBus);
    }

    /**
     * @covers ::execute
     */
    public function testExecute()
    {
        $expected = [
            'firstName' => 'Anna',
            'lastName'  => 'Rodygina',
        ];

        $query = new \stdClass();

        $result = $this->queryBus->execute($query);

        static::assertSame($expected, $result);
    }
}
