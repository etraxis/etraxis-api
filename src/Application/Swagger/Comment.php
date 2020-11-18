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
class Comment
{
    /**
     * @API\Property(type="integer", example=123, description="Comment ID.")
     */
    public int $id;

    /**
     * @API\Property(type="object", ref=@Model(type=eTraxis\Application\Swagger\UserInfo::class), description="Author of the comment.")
     */
    public UserInfo $user;

    /**
     * @API\Property(type="integer", example=1089280800, description="Unix Epoch timestamp when the comment has been posted.")
     */
    public int $timestamp;

    /**
     * @API\Property(type="string", example="Lorem ipsum", description="Text of the comment.")
     */
    public string $text;

    /**
     * @API\Property(type="boolean", example=false, description="Whether the comment is private.")
     */
    public bool $private;
}
