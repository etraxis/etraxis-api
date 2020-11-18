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

use eTraxis\Application\Command\Fields\UpdateNumberFieldCommand;

/**
 * Command handler.
 */
class UpdateNumberFieldHandler extends AbstractUpdateFieldHandler
{
    use HandlerTrait\NumberHandlerTrait;

    /**
     * Command handler.
     *
     * @param UpdateNumberFieldCommand $command
     */
    public function __invoke(UpdateNumberFieldCommand $command): void
    {
        $this->update($command);
    }
}
