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
 * Abstract "Update field" command.
 * Contains properties which are common for all commands to update specified field of any type.
 *
 * @property int $field Field ID.
 */
abstract class AbstractUpdateFieldCommand extends AbstractFieldCommand
{
    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\d+$/")
     */
    public ?int $field;
}
