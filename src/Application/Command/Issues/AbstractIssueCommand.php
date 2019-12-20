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

namespace eTraxis\Application\Command\Issues;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Abstract issue command.
 *
 * @property int   $responsible ID of user to assign the issue to (ignored when not applicable).
 * @property array $fields      Fields values (keys are field IDs).
 */
abstract class AbstractIssueCommand
{
    /**
     * @Assert\Regex("/^\d+$/")
     */
    public $responsible;

    /**
     * All the constraints are configured at run-time.
     */
    public $fields = [];
}
