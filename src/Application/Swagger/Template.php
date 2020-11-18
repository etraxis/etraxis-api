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
class Template
{
    /**
     * @API\Property(type="integer", example=123, description="Template ID.")
     */
    public int $id;

    /**
     * @API\Property(type="object", ref=@Model(type=eTraxis\Application\Swagger\Project::class), description="Template project.")
     */
    public Project $project;

    /**
     * @API\Property(type="string", example="Bugfix", description="Template name.")
     */
    public string $name;

    /**
     * @API\Property(type="string", example="bug", description="Template prefix (used as a prefix in ID of the issues, created using this template).")
     */
    public string $prefix;

    /**
     * @API\Property(type="string", example="Error reports", description="Optional description.")
     */
    public ?string $description;

    /**
     * @API\Property(type="integer", example=5, description="When an issue remains opened for more than this amount of days it's displayed in red in the list of issues.")
     */
    public ?int $critical;

    /**
     * @API\Property(type="integer", example=10, description="When an issue is closed a user cannot change its state anymore, but one still can edit its fields, add comments and attach files. If frozen time is specified it will be allowed to edit the issue this amount of days after its closure. After that the issue becomes read-only. If this attribute is not specified, an issue will never become read-only.")
     */
    public ?int $frozen;

    /**
     * @API\Property(type="boolean", example=false, description="Whether the template is locked for edition.")
     */
    public bool $locked;

    /**
     * @API\Property(type="array", description="List of HATEOAS links.", @API\Items(
     *     type="object",
     *     properties={
     *         @API\Property(property="rel",  type="string", example="self", description="API link related to the template."),
     *         @API\Property(property="href", type="string", example="https://example.com/api/templates/123", description="Absolute URL of the link."),
     *         @API\Property(property="type", type="string", example="GET", description="HTTP method of the link.")
     *     }
     * ))
     */
    public array $links;
}
