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
 * Updates specified state.
 *
 * @property int    $state       State ID.
 * @property string $name        New state name.
 * @property string $responsible New type of responsibility management.
 * @property int    $next        ID of the state which is next by default.
 */
class UpdateStateCommand
{
    use DataTransferObjectTrait;

    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\d+$/")
     */
    public $state;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="50")
     *
     * @Groups("api")
     * @API\Property(type="string", maxLength=50, example="Bugfix", description="State name.")
     */
    public $name;

    /**
     * @Assert\NotNull
     * @Assert\Choice(callback={"eTraxis\Application\Dictionary\StateResponsible", "keys"}, strict=true)
     *
     * @Groups("api")
     * @API\Property(type="string", enum={"keep", "assign", "remove"}, example="assign", description="State responsibility.")
     */
    public $responsible;

    /**
     * @Assert\Regex("/^\d+$/")
     *
     * @Groups("api")
     * @API\Property(type="integer", example=456, description="ID of the next state.")
     */
    public $next;
}
