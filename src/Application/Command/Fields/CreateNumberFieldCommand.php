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

use Webinarium\DataTransferObjectTrait;

/**
 * Creates new "number" field.
 */
class CreateNumberFieldCommand extends AbstractCreateFieldCommand
{
    use DataTransferObjectTrait;
    use CommandTrait\NumberCommandTrait;
}
