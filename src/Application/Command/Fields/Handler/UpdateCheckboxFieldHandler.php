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

use eTraxis\Application\Command\Fields\UpdateCheckboxFieldCommand;

/**
 * Command handler.
 */
class UpdateCheckboxFieldHandler extends AbstractUpdateFieldHandler
{
    use HandlerTrait\CheckboxHandlerTrait;

    /**
     * Command handler.
     *
     * @param UpdateCheckboxFieldCommand $command
     */
    public function __invoke(UpdateCheckboxFieldCommand $command): void
    {
        $this->update($command);
    }
}
