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

namespace eTraxis\Application\Command\ListItems;

use Symfony\Component\Validator\Constraints as Assert;
use Webinarium\DataTransferObjectTrait;

/**
 * Deletes specified list item.
 *
 * @property int $item Item ID.
 */
class DeleteListItemCommand
{
    use DataTransferObjectTrait;

    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\d+$/")
     */
    public ?int $item;
}
