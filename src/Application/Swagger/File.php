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
class File
{
    /**
     * @API\Property(type="integer", example=123, description="File ID.")
     */
    public int $id;

    /**
     * @API\Property(type="object", ref=@Model(type=eTraxis\Application\Swagger\UserInfo::class), description="User who attached the file.")
     */
    public UserInfo $user;

    /**
     * @API\Property(type="integer", example=1089280800, description="Unix Epoch timestamp when the file has been attached.")
     */
    public int $timestamp;

    /**
     * @API\Property(type="string", example="enclosure.pdf", description="File name.")
     */
    public string $name;

    /**
     * @API\Property(type="integer", example=233074, description="File size (bytes).")
     */
    public int $size;

    /**
     * @API\Property(type="string", example="application/pdf", description="MIME type.")
     */
    public string $type;

    /**
     * @API\Property(type="array", description="List of HATEOAS links.", @API\Items(
     *     type="object",
     *     properties={
     *         @API\Property(property="rel",  type="string", example="self", description="API link related to the file."),
     *         @API\Property(property="href", type="string", example="https://example.com/api/files/123", description="Absolute URL of the link."),
     *         @API\Property(property="type", type="string", example="GET", description="HTTP method of the link.")
     *     }
     * ))
     */
    public array $links;
}
