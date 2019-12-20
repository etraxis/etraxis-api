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
     */
    public $template;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="50")
     */
    public $name;

    /**
     * @Assert\NotNull
     * @Assert\Choice(callback={"eTraxis\Application\Dictionary\StateType", "keys"}, strict=true)
     */
    public $type;

    /**
     * @Assert\NotNull
     * @Assert\Choice(callback={"eTraxis\Application\Dictionary\StateResponsible", "keys"}, strict=true)
     */
    public $responsible;

    /**
     * @Assert\Regex("/^\d+$/")
     */
    public $next;
}
