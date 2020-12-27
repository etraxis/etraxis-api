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

namespace eTraxis\MessageBus\Middleware;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Middleware\StackMiddleware;
use Symfony\Component\Messenger\Stamp\BusNameStamp;

/**
 * @coversDefaultClass \eTraxis\MessageBus\Middleware\HttpExceptionMiddleware
 */
class HttpExceptionMiddlewareTest extends TestCase
{
    /**
     * @covers ::handle
     */
    public function testHandleNoException()
    {
        $stack = new class() implements StackInterface {
            public function next(): MiddlewareInterface
            {
                return new class() extends StackMiddleware {
                    public function handle(Envelope $envelope, StackInterface $stack): Envelope
                    {
                        return $envelope->with(new BusNameStamp('test.bus'));
                    }
                };
            }
        };

        $message    = new \stdClass();
        $envelope   = new Envelope($message);
        $middleware = new HttpExceptionMiddleware();

        $envelope = $middleware->handle($envelope, $stack);

        static::assertNotNull($envelope->last(BusNameStamp::class));
    }

    /**
     * @covers ::handle
     */
    public function testHandleHttpException()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Entity is not found.');

        $stack = new class() implements StackInterface {
            public function next(): MiddlewareInterface
            {
                return new class() extends StackMiddleware {
                    public function handle(Envelope $envelope, StackInterface $stack): Envelope
                    {
                        $exception = new NotFoundHttpException('Entity is not found.');
                        throw new HandlerFailedException($envelope, [$exception]);
                    }
                };
            }
        };

        $message    = new \stdClass();
        $envelope   = new Envelope($message);
        $middleware = new HttpExceptionMiddleware();

        $middleware->handle($envelope, $stack);
    }

    /**
     * @covers ::handle
     */
    public function testHandleNotHttpException()
    {
        $this->expectException(HandlerFailedException::class);
        $this->expectExceptionMessage('Something went wrong.');

        $stack = new class() implements StackInterface {
            public function next(): MiddlewareInterface
            {
                return new class() extends StackMiddleware {
                    public function handle(Envelope $envelope, StackInterface $stack): Envelope
                    {
                        $exception = new \LogicException('Something went wrong.');
                        throw new HandlerFailedException($envelope, [$exception]);
                    }
                };
            }
        };

        $message    = new \stdClass();
        $envelope   = new Envelope($message);
        $middleware = new HttpExceptionMiddleware();

        $middleware->handle($envelope, $stack);
    }
}
