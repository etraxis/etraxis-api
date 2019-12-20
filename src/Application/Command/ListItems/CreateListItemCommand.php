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

use Symfony\Component\Validator\Constraints as Assert;
use Webinarium\DataTransferObjectTrait;

/**
 * Creates new list item.
 *
 * @property int    $field ID of the item's field.
 * @property int    $value Value of the item.
 * @property string $text  Text of the item.
 */
class CreateListItemCommand
{
    use DataTransferObjectTrait;

    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\d+$/")
     */
    public $field;

    /**
     * @Assert\Range(min="1")
     */
    public $value;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="50")
     */
    public $text;
}
