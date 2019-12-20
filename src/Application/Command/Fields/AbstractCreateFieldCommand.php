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

/**
 * Abstract "Create field" command.
 * Contains properties which are common for all commands to create new field of any type.
 *
 * @property int $state ID of the field's state.
 */
abstract class AbstractCreateFieldCommand extends AbstractFieldCommand
{
    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\d+$/")
     */
    public $state;
}
