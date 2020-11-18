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

namespace eTraxis\Application\Command\Fields;

use Webinarium\DataTransferObjectTrait;

/**
 * Updates specified "decimal" field.
 */
class UpdateDecimalFieldCommand extends AbstractUpdateFieldCommand
{
    use DataTransferObjectTrait;
    use CommandTrait\DecimalCommandTrait;
}
