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

namespace eTraxis\Application\Command\Fields\Handler;

use eTraxis\Application\Command\Fields\CreateListFieldCommand;
use eTraxis\Entity\Field;

/**
 * Command handler.
 */
class CreateListFieldHandler extends AbstractCreateFieldHandler
{
    use HandlerTrait\ListHandlerTrait;

    /**
     * Command handler.
     *
     * @param CreateListFieldCommand $command
     *
     * @return Field
     */
    public function __invoke(CreateListFieldCommand $command): Field
    {
        return $this->create($command);
    }
}
