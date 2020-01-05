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
class File
{
    /**
     * @API\Property(type="integer", example=123, description="File ID.")
     */
    public $id;

    /**
     * @API\Property(type="object", ref=@Model(type=eTraxis\Application\Swagger\UserInfo::class), description="User who attached the file.")
     */
    public $user;

    /**
     * @API\Property(type="integer", example=1089280800, description="Unix Epoch timestamp when the file has been attached.")
     */
    public $timestamp;

    /**
     * @API\Property(type="string", example="enclosure.pdf", description="File name.")
     */
    public $name;

    /**
     * @API\Property(type="integer", example=233074, description="File size (bytes).")
     */
    public $size;

    /**
     * @API\Property(type="string", example="application/pdf", description="MIME type.")
     */
    public $type;
}
