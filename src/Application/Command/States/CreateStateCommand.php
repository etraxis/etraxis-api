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

namespace eTraxis\Application\Command\States;

use Swagger\Annotations as API;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Webinarium\DataTransferObjectTrait;

/**
 * Creates new state.
 *
 * @property int    $template    ID of the state's template.
 * @property string $name        State name.
 * @property string $type        Type of the state.
 * @property string $responsible Type of responsibility management.
 * @property int    $next        ID of the state which is next by default.
 */
class CreateStateCommand
{
    use DataTransferObjectTrait;

    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\d+$/")
     *
     * @Groups("api")
     * @API\Property(type="integer", example=123, description="Template ID.")
     */
    public int $template;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="50")
     *
     * @Groups("api")
     * @API\Property(type="string", maxLength=50, example="Bugfix", description="State name.")
     */
    public string $name;

    /**
     * @Assert\NotNull
     * @Assert\Choice(callback={"eTraxis\Application\Dictionary\StateType", "keys"}, strict=true)
     *
     * @Groups("api")
     * @API\Property(type="string", enum={"initial", "intermediate", "final"}, example="intermediate", description="State type.")
     */
    public string $type;

    /**
     * @Assert\NotNull
     * @Assert\Choice(callback={"eTraxis\Application\Dictionary\StateResponsible", "keys"}, strict=true)
     *
     * @Groups("api")
     * @API\Property(type="string", enum={"keep", "assign", "remove"}, example="assign", description="State responsibility.")
     */
    public string $responsible;

    /**
     * @Assert\Regex("/^\d+$/")
     *
     * @Groups("api")
     * @API\Property(type="integer", example=456, description="ID of the next state.")
     */
    public ?int $next = null;
}
