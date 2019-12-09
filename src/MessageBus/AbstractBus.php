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
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Messenger\Stamp\HandledStamp;

abstract class AbstractBus
{
    /**
     * Asserts that the specified message was handled by expected bus.
     *
     * @param string   $busName  Name of the expected bus.
     * @param Envelope $envelope Handled message envelope.
     *
     * @throws \LogicException If the message was handled by the wrong bus.
     */
    protected function assertBusName(string $busName, Envelope $envelope): void
    {
        /** @var BusNameStamp $stamp */
        $stamp = $envelope->last(BusNameStamp::class);

        if ($stamp->getBusName() !== $busName) {
            throw new \LogicException(sprintf(
                'The %s message must be handled by the "%s" service but was dispatched to the "%s" one.',
                get_class($envelope->getMessage()),
                $busName,
                $stamp->getBusName()
            ));
        }
    }

    /**
     * Extracts the result from the handled message.
     *
     * @param Envelope $envelope Handled message envelope.
     *
     * @return mixed Message result.
     */
    protected function getMessageResult(Envelope $envelope)
    {
        /** @var HandledStamp $stamp */
        $stamp = $envelope->last(HandledStamp::class);

        return $stamp->getResult();
    }
}
