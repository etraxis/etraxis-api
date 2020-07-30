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

use Symfony\Component\Messenger\MessageBusInterface;

class QueryBus extends AbstractBus implements Contracts\QueryBusInterface
{
    private const BUS_NAME = 'query.bus';

    private MessageBusInterface $queryBus;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param MessageBusInterface $queryBus
     */
    public function __construct(MessageBusInterface $queryBus)
    {
        $this->queryBus = $queryBus;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($query)
    {
        $envelope = $this->queryBus->dispatch($query);

        $this->assertBusName(self::BUS_NAME, $envelope);

        return $this->getMessageResult($envelope);
    }
}
