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
     */
    public $default;
}
