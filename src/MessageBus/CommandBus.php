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

use Symfony\Component\Messenger\MessageBusInterface;

class CommandBus extends AbstractBus implements Contracts\CommandBusInterface
{
    private const BUS_NAME = 'command.bus';

    private MessageBusInterface $commandBus;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param MessageBusInterface $commandBus
     */
    public function __construct(MessageBusInterface $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    /**
     * {@inheritdoc}
     */
    public function handle($command)
    {
        $envelope = $this->commandBus->dispatch($command);

        $this->assertBusName(self::BUS_NAME, $envelope);

        return $this->getMessageResult($envelope);
    }
}
