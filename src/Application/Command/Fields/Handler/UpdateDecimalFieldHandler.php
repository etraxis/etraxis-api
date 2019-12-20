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

namespace eTraxis\Application\Command\Fields\Handler;

use eTraxis\Application\Command\Fields\UpdateDecimalFieldCommand;

/**
 * Command handler.
 */
class UpdateDecimalFieldHandler extends AbstractUpdateFieldHandler
{
    use HandlerTrait\DecimalHandlerTrait;

    /**
     * Command handler.
     *
     * @param UpdateDecimalFieldCommand $command
     */
    public function __invoke(UpdateDecimalFieldCommand $command): void
    {
        $this->update($command);
    }
}
