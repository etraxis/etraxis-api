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

namespace eTraxis\MessageBus\Contracts;

use Symfony\Component\Messenger\Envelope;

/**
 * Query bus.
 */
interface QueryBusInterface
{
    /**
     * Executes the given query.
     *
     * @param Envelope|object $query The query or the query pre-wrapped in an envelope.
     *
     * @return mixed Query results.
     */
    public function execute($query);
}
