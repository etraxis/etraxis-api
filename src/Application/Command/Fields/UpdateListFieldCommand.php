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

namespace eTraxis\Application\Command\Fields;

use Swagger\Annotations as API;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Webinarium\DataTransferObjectTrait;

/**
 * Updates specified "list" field.
 *
 * @property int $default ListItem ID.
 */
class UpdateListFieldCommand extends AbstractUpdateFieldCommand
{
    use DataTransferObjectTrait;
    use CommandTrait\ListCommandTrait;

    /**
     * @Assert\Regex("/^\d+$/")
     *
     * @Groups("api")
     * @API\Property(type="integer", example=123, description="Default value (list item ID).")
     */
    public $default;
}
