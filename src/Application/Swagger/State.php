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

namespace eTraxis\Application\Swagger;

use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as API;

/**
 * Descriptive class for API annotations.
 */
class State
{
    /**
     * @API\Property(type="integer", example=123, description="State ID.")
     */
    public $id;

    /**
     * @API\Property(type="object", ref=@Model(type=eTraxis\Application\Swagger\Template::class), description="State template.")
     */
    public $template;

    /**
     * @API\Property(type="string", example="Assigned", description="State name.")
     */
    public $name;

    /**
     * @API\Property(type="string", enum={"initial", "intermediate", "final"}, example="intermediate", description="State type.")
     */
    public $type;

    /**
     * @API\Property(type="string", enum={"keep", "assign", "remove"}, example="assign", description="State responsibility.")
     */
    public $responsible;

    /**
     * @API\Property(type="integer", example=456, description="ID of the next state if specified.")
     */
    public $next;
}
