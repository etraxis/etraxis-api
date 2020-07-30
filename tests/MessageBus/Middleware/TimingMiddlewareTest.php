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

namespace eTraxis\MessageBus\Middleware;

use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Middleware\StackMiddleware;
use Symfony\Component\Messenger\Stamp\BusNameStamp;

/**
 * @coversDefaultClass \eTraxis\MessageBus\Middleware\TimingMiddleware
 */
class TimingMiddlewareTest extends TestCase
{
    /**
     * @covers ::handle
     */
    public function testHandle()
    {
        $logger = new class() extends AbstractLogger {
            private string $logs;

            public function __construct()
            {
                $this->logs = '';
            }

            public function log($level, $message, array $context = [])
            {
                $this->logs .= $message;
            }

            public function contains($message)
            {
                return mb_strpos($this->logs, $message) !== false;
            }
        };

        $stack = new class() implements StackInterface {
            public function next(): MiddlewareInterface
            {
                return new StackMiddleware();
            }
        };

        $message  = new \stdClass();
        $stamp    = new BusNameStamp('test.bus');
        $envelope = new Envelope($message, [$stamp]);

        $middleware = new TimingMiddleware($logger);
        $middleware->handle($envelope, $stack);

        self::assertTrue($logger->contains('Message processing time'));
    }
}
