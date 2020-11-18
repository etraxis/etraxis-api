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
class Change
{
    /**
     * @API\Property(type="object", ref=@Model(type=eTraxis\Application\Swagger\UserInfo::class), description="Author of the change.")
     */
    public UserInfo $user;

    /**
     * @API\Property(type="integer", example=1089280800, description="Unix Epoch timestamp when the change has been made.")
     */
    public int $timestamp;

    /**
     * @API\Property(type="object", ref=@Model(type=eTraxis\Application\Swagger\FieldInfo::class), description="Changed field (NULL for the subject).")
     */
    public FieldInfo $field;

    /**
     * @API\Property(type="", example=1, description="Old value of the field (depends on the field type).")
     */
    public int $old_value;

    /**
     * @API\Property(type="", example=2, description="New value of the field (depends on the field type).")
     */
    public int $new_value;
}
