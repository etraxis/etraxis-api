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

namespace eTraxis\Application\Command\ListItems;

use Swagger\Annotations as API;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Webinarium\DataTransferObjectTrait;

/**
 * Updates specified list item.
 *
 * @property int    $item  Item ID.
 * @property int    $value New value of the item.
 * @property string $text  New text of the item.
 */
class UpdateListItemCommand
{
    use DataTransferObjectTrait;

    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\d+$/")
     */
    public int $item;

    /**
     * @Assert\Range(min="1")
     *
     * @Groups("api")
     * @API\Property(type="integer", minimum=1, example=5, description="Item's value.")
     */
    public int $value;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="50")
     *
     * @Groups("api")
     * @API\Property(type="string", maxLength=50, example="Friday", description="Item's text.")
     */
    public string $text;
}
