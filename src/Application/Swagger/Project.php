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

use Swagger\Annotations as API;

/**
 * Descriptive class for API annotations.
 */
class Project
{
    /**
     * @API\Property(type="integer", example=123, description="Project ID.")
     */
    public int $id;

    /**
     * @API\Property(type="string", example="Project A", description="Project name.")
     */
    public string $name;

    /**
     * @API\Property(type="string", example="Our initial startup", description="Optional description.")
     */
    public ?string $description;

    /**
     * @API\Property(type="integer", example=1089280800, description="Unix Epoch timestamp when the project has been registered.")
     */
    public int $created;

    /**
     * @API\Property(type="boolean", example=false, description="Whether the project is suspended.")
     */
    public bool $suspended;

    /**
     * @API\Property(type="array", description="List of HATEOAS links.", @API\Items(
     *     type="object",
     *     properties={
     *         @API\Property(property="rel",  type="string", example="self", description="API link related to the project."),
     *         @API\Property(property="href", type="string", example="https://example.com/api/projects/123", description="Absolute URL of the link."),
     *         @API\Property(property="type", type="string", example="GET", description="HTTP method of the link.")
     *     }
     * ))
     */
    public array $links;
}
