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
class Group
{
    /**
     * @API\Property(type="integer", example=123, description="Group ID.")
     */
    public $id;

    /**
     * @API\Property(type="object", ref=@Model(type=eTraxis\Application\Swagger\Project::class), description="Group project (null if the group is global).")
     */
    public $project;

    /**
     * @API\Property(type="string", example="Team", description="Group name.")
     */
    public $name;

    /**
     * @API\Property(type="string", example="Project developers", description="Optional description.")
     */
    public $description;

    /**
     * @API\Property(type="boolean", example=false, description="Whether the group is a global one.")
     */
    public $global;

    /**
     * @API\Property(type="array", description="List of HATEOAS links.", @API\Items(
     *     type="object",
     *     properties={
     *         @API\Property(property="rel",  type="string", example="self", description="API link related to the group."),
     *         @API\Property(property="href", type="string", example="https://example.com/api/groups/123", description="Absolute URL of the link."),
     *         @API\Property(property="type", type="string", example="GET", description="HTTP method of the link.")
     *     }
     * ))
     */
    public $links;
}
