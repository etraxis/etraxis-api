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

use eTraxis\Application\Command\Fields\CreateIssueFieldCommand;
use eTraxis\Entity\Field;

/**
 * Command handler.
 */
class CreateIssueFieldHandler extends AbstractCreateFieldHandler
{
    use HandlerTrait\IssueHandlerTrait;

    /**
     * Command handler.
     *
     * @param CreateIssueFieldCommand $command
     *
     * @return Field
     */
    public function __invoke(CreateIssueFieldCommand $command): Field
    {
        return $this->create($command);
    }
}
