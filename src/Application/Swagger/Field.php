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

namespace eTraxis\Application\Swagger;

use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as API;

/**
 * Descriptive class for API annotations.
 */
class Field
{
    /**
     * @API\Property(type="integer", example=123, description="Field ID.")
     */
    public int $id;

    /**
     * @API\Property(type="object", ref=@Model(type=eTraxis\Application\Swagger\State::class), description="Field state.")
     */
    public State $state;

    /**
     * @API\Property(type="string", example="Severity", description="Field name.")
     */
    public string $name;

    /**
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
    public string $type;

    /**
     * @API\Property(type="string", example="Error severity", description="Optional description.")
     */
    public ?string $description;

    /**
     * @API\Property(type="integer", example=1, description="Ordinal number of the field among other fields of the same state.")
     */
    public int $position;

    /**
     * @API\Property(type="boolean", example=true, description="Whether the field is required.")
     */
    public bool $required;

    /**
     * @API\Property(type="array", description="List of HATEOAS links.", @API\Items(
     *     type="object",
     *     properties={
     *         @API\Property(property="rel",  type="string", example="self", description="API link related to the field."),
     *         @API\Property(property="href", type="string", example="https://example.com/api/fields/123", description="Absolute URL of the link."),
     *         @API\Property(property="type", type="string", example="GET", description="HTTP method of the link.")
     *     }
     * ))
     */
    public array $links;
}
