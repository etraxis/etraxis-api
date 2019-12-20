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

use eTraxis\Application\Command\Fields\UpdateStringFieldCommand;

/**
 * Command handler.
 */
class UpdateStringFieldHandler extends AbstractUpdateFieldHandler
{
    use HandlerTrait\StringHandlerTrait;

    /**
     * Command handler.
     *
     * @param UpdateStringFieldCommand $command
     */
    public function __invoke(UpdateStringFieldCommand $command): void
    {
        $this->update($command);
    }
}
