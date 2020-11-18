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

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

/**
 * Middleware to propagate HTTP exceptions further.
 */
class HttpExceptionMiddleware implements MiddlewareInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        try {
            return $stack->next()->handle($envelope, $stack);
        }
        catch (\Throwable $throwable) {

            if ($throwable instanceof HandlerFailedException) {

                $exceptions = $throwable->getNestedExceptions();
                $exception  = reset($exceptions);

                if ($exception instanceof HttpException) {
                    throw $exception;
                }
            }

            throw $throwable;
        }
    }
}
