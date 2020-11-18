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

namespace eTraxis\Application\Command\Issues;

use Swagger\Annotations as API;
use Symfony\Component\Serializer\Annotation\Groups;
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
     *
     * @Groups("api")
     * @API\Property(type="integer", example=123, description="User ID, who should be assigned to the issue.")
     */
    public ?int $responsible;

    /**
     * All the constraints are configured at run-time.
     *
     * @Groups("api")
     * @API\Property(type="object", description="Fields values (keys are field IDs).")
     */
    public ?array $fields = [];
}
