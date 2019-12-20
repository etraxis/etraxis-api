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
    public $item;

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
