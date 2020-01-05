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
class Issue
{
    /**
     * @API\Property(type="integer", example=123, description="Issue ID.")
     */
    public $id;

    /**
     * @API\Property(type="string", example="Test issue", description="Subject of the issue.")
     */
    public $subject;

    /**
     * @API\Property(type="integer", example=1089280800, description="Unix Epoch timestamp when the issue has been created.")
     */
    public $created_at;

    /**
     * @API\Property(type="integer", example=1089280800, description="Unix Epoch timestamp when the issue has been changed last time.")
     */
    public $changed_at;

    /**
     * @API\Property(type="integer", example=1089280800, description="Unix Epoch timestamp when the issue has been closed, if so.")
     */
    public $closed_at;

    /**
     * @API\Property(type="object", ref=@Model(type=eTraxis\Application\Swagger\UserInfo::class), description="Author of the issue.")
     */
    public $author;

    /**
     * @API\Property(type="object", ref=@Model(type=eTraxis\Application\Swagger\State::class), description="Current state.")
     */
    public $state;

    /**
     * @API\Property(type="object", ref=@Model(type=eTraxis\Application\Swagger\UserInfo::class), description="Current responsible of the issue.")
     */
    public $responsible;

    /**
     * @API\Property(type="boolean", example=true, description="Whether the issue was cloned.")
     */
    public $is_cloned;

    /**
     * @API\Property(type="integer", example=123, description="Original issue ID this issue was cloned from (when applicable).")
     */
    public $origin;

    /**
     * @API\Property(type="integer", example=5, description="Number of days the issue remained or remains opened.")
     */
    public $age;

    /**
     * @API\Property(type="boolean", example=false, description="Whether the issue is critical (remains opened for too long).")
     */
    public $is_critical;

    /**
     * @API\Property(type="boolean", example=true, description="Whether the issue is suspended.")
     */
    public $is_suspended;

    /**
     * @API\Property(type="integer", example=1089280800, description="Unix Epoch timestamp when the issue will be resumed, if suspended.")
     */
    public $resumes_at;

    /**
     * @API\Property(type="boolean", example=false, description="Whether the issue is closed.")
     */
    public $is_closed;

    /**
     * @API\Property(type="boolean", example=false, description="Whether the issue is frozen.")
     */
    public $is_frozen;

    /**
     * @API\Property(type="integer", example=1089280800, description="Unix Epoch timestamp when the issue was viewed by current user last time.")
     */
    public $read_at;
}
