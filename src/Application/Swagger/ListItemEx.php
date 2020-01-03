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
class ListItemEx
{
    /**
     * @API\Property(type="integer", example=123, description="Item ID.")
     */
    public $id;

    /**
     * @API\Property(type="object", ref=@Model(type=eTraxis\Application\Swagger\Field::class), description="Item's field.")
     */
    public $field;

    /**
     * @API\Property(type="integer", example=5, description="Item's value.")
     */
    public $value;

    /**
     * @API\Property(type="string", example="Friday", description="Item's text.")
     */
    public $text;
}
