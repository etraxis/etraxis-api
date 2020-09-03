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

namespace eTraxis\Application\Command\Fields;

use Swagger\Annotations as API;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Abstract "Create field" command.
 * Contains properties which are common for all commands to create new field of any type.
 *
 * @property int $state ID of the field's state.
 */
abstract class AbstractCreateFieldCommand extends AbstractFieldCommand
{
    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\d+$/")
     *
     * @Groups("api")
     * @API\Property(type="integer", example=123, description="State ID.")
     */
    public ?int $state;

    /**
     * @internal Descriptive property for API annotations.
     *
     * @Groups("api")
     * @API\Property(type="string", enum={
     *     "checkbox",
     *     "date",
     *     "decimal",
     *     "duration",
     *     "issue",
     *     "list",
     *     "number",
     *     "string",
     *     "text"
     * }, example="list", description="Field type.")
     */
    protected string $type;
}
