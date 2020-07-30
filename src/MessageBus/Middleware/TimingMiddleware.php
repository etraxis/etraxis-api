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

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\BusNameStamp;

/**
 * Middleware to calculate message processing time.
 */
class TimingMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $start = microtime(true);

        try {
            return $stack->next()->handle($envelope, $stack);
        }
        finally {
            /** @var BusNameStamp $stamp */
            $stamp = $envelope->last(BusNameStamp::class);
            $stop  = microtime(true);

            $this->logger->debug('Message processing time', [
                'bus'   => $stamp->getBusName(),
                'time'  => $stop - $start,
                'class' => get_class($envelope->getMessage()),
            ]);
        }
    }
}
