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
class Issue
{
    /**
     * @API\Property(type="integer", example=123, description="Issue ID.")
     */
    public int $id;

    /**
     * @API\Property(type="string", example="Test issue", description="Subject of the issue.")
     */
    public string $subject;

    /**
     * @API\Property(type="integer", example=1089280800, description="Unix Epoch timestamp when the issue has been created.")
     */
    public int $created_at;

    /**
     * @API\Property(type="integer", example=1089280800, description="Unix Epoch timestamp when the issue has been changed last time.")
     */
    public int $changed_at;

    /**
     * @API\Property(type="integer", example=1089280800, description="Unix Epoch timestamp when the issue has been closed, if so.")
     */
    public ?int $closed_at;

    /**
     * @API\Property(type="object", ref=@Model(type=eTraxis\Application\Swagger\UserInfo::class), description="Author of the issue.")
     */
    public UserInfo $author;

    /**
     * @API\Property(type="object", ref=@Model(type=eTraxis\Application\Swagger\State::class), description="Current state.")
     */
    public State $state;

    /**
     * @API\Property(type="object", ref=@Model(type=eTraxis\Application\Swagger\UserInfo::class), description="Current responsible of the issue.")
     */
    public ?UserInfo $responsible;

    /**
     * @API\Property(type="boolean", example=true, description="Whether the issue was cloned.")
     */
    public bool $is_cloned;

    /**
     * @API\Property(type="integer", example=123, description="Original issue ID this issue was cloned from (when applicable).")
     */
    public ?int $origin;

    /**
     * @API\Property(type="integer", example=5, description="Number of days the issue remained or remains opened.")
     */
    public int $age;

    /**
     * @API\Property(type="boolean", example=false, description="Whether the issue is critical (remains opened for too long).")
     */
    public bool $is_critical;

    /**
     * @API\Property(type="boolean", example=true, description="Whether the issue is suspended.")
     */
    public bool $is_suspended;

    /**
     * @API\Property(type="integer", example=1089280800, description="Unix Epoch timestamp when the issue will be resumed, if suspended.")
     */
    public ?int $resumes_at;

    /**
     * @API\Property(type="boolean", example=false, description="Whether the issue is closed.")
     */
    public bool $is_closed;

    /**
     * @API\Property(type="boolean", example=false, description="Whether the issue is frozen.")
     */
    public bool $is_frozen;

    /**
     * @API\Property(type="integer", example=1089280800, description="Unix Epoch timestamp when the issue was viewed by current user last time.")
     */
    public ?int $read_at;

    /**
     * @API\Property(type="array", description="List of HATEOAS links.", @API\Items(
     *     type="object",
     *     properties={
     *         @API\Property(property="rel",  type="string", example="self", description="API link related to the issue."),
     *         @API\Property(property="href", type="string", example="https://example.com/api/issues/123", description="Absolute URL of the link."),
     *         @API\Property(property="type", type="string", example="GET", description="HTTP method of the link.")
     *     }
     * ))
     */
    public array $links;
}
