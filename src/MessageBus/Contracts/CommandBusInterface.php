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

namespace eTraxis\MessageBus\Contracts;

use Symfony\Component\Messenger\Envelope;

/**
 * Command bus.
 */
interface CommandBusInterface
{
    /**
     * Handles the given command.
     *
     * @param Envelope|object $command The command or the command pre-wrapped in an envelope.
     *
     * @return mixed A result from command processing.
     */
    public function handle($command);
}
